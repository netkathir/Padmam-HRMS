<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BusinessRule;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeNumberGenerator;
use App\Support\BranchScope;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    private const LABOUR_TYPE_MAP = ['staff' => null, 'company_labour' => 'company_labour', 'contract_labour' => 'contract_labour'];

    public function index(Request $request)
    {
        $query = Employee::with('branch')
            ->orderByDesc('created_at');

        $query = BranchScope::scopeQuery($query);

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('employee_code', 'like', $s)
                ->orWhere('official_email', 'like', $s));
        }

        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped actor
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status'))        $query->where('status', $request->status);

        $employees   = $query->paginate(20)->withQueryString();
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        $branches    = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();

        return view('employees.index', compact('employees', 'departments', 'branches'));
    }

    public function create()
    {
        $activeTab = 2;
        $primaryBankDetail = null;

        return view('employees.create', $this->formData() + compact('activeTab', 'primaryBankDetail'));
    }

    /**
     * Live "what code will this employee get" preview for the Create form —
     * read-only, never consumes a Rule Engine sequence number (uses
     * EmployeeNumberGenerator::preview(), the same non-consuming method the
     * Rule Engine's own "Sample Employee Number" field uses) so simply
     * loading/changing the form can never burn a real sequence value. The
     * actual code is always re-resolved for real at submit time regardless
     * of what this preview showed.
     */
    public function previewEmployeeCode(Request $request)
    {
        $category = $request->input('employee_category');
        if (! in_array($category, ['staff', 'company_labour', 'contract_labour'], true)) {
            return response()->json(['code' => null]);
        }

        $primaryType = $category === 'staff' ? 'staff' : 'labour';
        $labourType = self::LABOUR_TYPE_MAP[$category];
        $branchId = BranchScope::currentBranchId();
        $contractorId = $request->integer('contractor_id') ?: null;

        $generator = app(EmployeeNumberGenerator::class);
        $rule = $generator->resolveRule($primaryType, $labourType, $branchId, $contractorId);

        if ($rule) {
            return response()->json(['code' => $generator->preview($rule, $branchId, $contractorId)]);
        }

        $prefix = $generator->typePrefix($request->integer('employee_type_id') ?: null);
        $lastCode = Employee::where('employee_code', 'like', $prefix . '%')->orderByDesc('id')->value('employee_code');

        return response()->json(['code' => SequentialCodeGenerator::next($lastCode, $prefix . '0001')]);
    }

    private function assertDepartmentIsActive(?int $departmentId, ?Employee $employee = null): void
    {
        if (! $departmentId || ($employee && $employee->department_id === $departmentId)) {
            return;
        }

        $department = Department::find($departmentId);
        if ($department && ! $department->is_active) {
            abort(422, 'The selected department is inactive and cannot be assigned to new employees.');
        }
    }

    /**
     * Decides the Employee Code to use: the applicable Employee Number
     * Rule's generated value (unless manual override is both permitted and
     * provided), or — when no rule is configured — the Employee-Type-prefixed
     * default sequence. Callable again on its own (with employee_code reset
     * to null) to regenerate a fresh candidate if the first one collides at
     * insert time. Delegates to EmployeeNumberGenerator so store(), update(),
     * and the employees:backfill-codes command all resolve a code the same
     * way.
     */
    private function resolveEmployeeCode(array $data, ?string $primaryType, ?string $labourType): ?string
    {
        return app(EmployeeNumberGenerator::class)->resolveEmployeeCode($data, $primaryType, $labourType);
    }

    /**
     * users.username (and users.name — FSD 15.1, "User Name... unique") are
     * both NOT NULL/unique with nothing else in this "Create Login User"
     * flow ever populating them. Derives a username from the email's local
     * part (before the @), appending a numeric suffix until it's unique.
     */
    private function generateUniqueUsername(string $email): string
    {
        $base = \Illuminate\Support\Str::slug(explode('@', $email)[0], '.') ?: 'user';

        return $this->makeUnique($base, 'username');
    }

    /** users.name is also unique (FSD 15.1) — two employees can share a full name, so this needs the same disambiguation. */
    private function generateUniqueDisplayName(string $fullName): string
    {
        return $this->makeUnique($fullName ?: 'User', 'name');
    }

    /** Appends a numeric suffix to $base until no users row has that value in $column. */
    private function makeUnique(string $base, string $column): string
    {
        $value = $base;
        $suffix = 1;

        while (User::where($column, $value)->exists()) {
            $value = $base . ' ' . $suffix++;
        }

        return $value;
    }

    /**
     * Creates the employee (and its login user, if requested) in a
     * transaction. The row lock in EmployeeNumberGenerator::generateDefaultCode()/
     * the Rule Engine's own counter lock make a collision here unlikely but not
     * provably impossible (the lock is released before this insert runs) —
     * if employee_code was auto-generated and this still collides, a fresh
     * code is generated and the insert is retried.
     */
    private function createEmployeeWithRetry(array $data, Request $request, bool $codeWasGenerated, ?string $primaryType, ?string $labourType): Employee
    {
        // See Masters\ShiftController::createWithGeneratedCode() for why
        // this needs more than a couple of retries plus a jittered backoff:
        // two near-simultaneous submissions can both read the same "last
        // code" and race for the same next value.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $request) {
                    $emp = Employee::create($data);

                    // Create login user if email provided
                    if ($request->filled('official_email') && $request->boolean('create_user')) {
                        User::create([
                            'name'        => $this->generateUniqueDisplayName($emp->full_name),
                            'username'    => $this->generateUniqueUsername($emp->official_email),
                            'email'       => $emp->official_email,
                            'password'    => Hash::make('Welcome@' . now()->year),
                            'role_id'     => $request->input('role_id', 2),
                            'employee_id' => $emp->id,
                            'is_active'   => true,
                        ]);
                    }

                    return $emp;
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || ! $codeWasGenerated || $attempt === 10) {
                    throw $e;
                }
                $data['employee_code'] = $this->resolveEmployeeCode(
                    array_merge($data, ['employee_code' => null]),
                    $primaryType,
                    $labourType
                );
                usleep(random_int(20_000, 80_000));
            }
        }

        throw new \RuntimeException('Unable to create the employee with a unique Employee Code after several attempts.');
    }

    /**
     * Same collision-retry guarantee as createEmployeeWithRetry(), for the
     * Employee Slab's Employment Information save — the first save after
     * creation is the one that actually generates employee_code, so it can
     * collide with a concurrently-generated code exactly like at creation.
     */
    private function updateEmployeeWithRetry(Employee $employee, array $data, bool $codeWasGenerated, ?string $primaryType, ?string $labourType): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                $employee->update($data);
                return;
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || ! $codeWasGenerated || $attempt === 10) {
                    throw $e;
                }
                $data['employee_code'] = $this->resolveEmployeeCode(
                    array_merge($data, ['employee_code' => null]),
                    $primaryType,
                    $labourType
                );
                usleep(random_int(20_000, 80_000));
            }
        }

        throw new \RuntimeException('Unable to save the employee with a unique Employee Code after several attempts.');
    }

    /**
     * FSD 10.3.3 — "Reporting manager shall not be the same employee."
     */
    private function assertReportingToIsNotSelf(?int $reportingTo, ?int $employeeId): void
    {
        if ($reportingTo !== null && $employeeId !== null && $reportingTo === $employeeId) {
            throw ValidationException::withMessages([
                'reporting_to' => 'An employee cannot report to themselves.',
            ]);
        }
    }

    /**
     * FSD 10.8 — Contract Labour: contractor mandatory, must be active, and
     * contract dates (if the contractor has an agreement period configured)
     * must fall within it. Contractor is a global master (no branch
     * mapping), so no branch-match check applies here.
     */
    private function assertContractorForLabour(array $data): void
    {
        if (($data['employee_category'] ?? null) !== 'contract_labour') {
            return;
        }

        if (empty($data['contractor_id'])) {
            throw ValidationException::withMessages(['contractor_id' => 'Contractor is mandatory for Contract Labour.']);
        }

        $contractor = Contractor::find($data['contractor_id']);
        if (! $contractor) {
            return;
        }

        if (! $contractor->is_active) {
            throw ValidationException::withMessages(['contractor_id' => 'The selected contractor is inactive.']);
        }

        if (! empty($data['contract_start_date'])) {
            if ($contractor->agreement_start_date && $data['contract_start_date'] < $contractor->agreement_start_date->toDateString()) {
                throw ValidationException::withMessages(['contract_start_date' => 'Contract start date is before the contractor\'s agreement start date.']);
            }
            $contractEnd = $data['contract_end_date'] ?? null;
            if ($contractEnd && $contractor->agreement_end_date && $contractEnd > $contractor->agreement_end_date->toDateString()) {
                throw ValidationException::withMessages(['contract_end_date' => 'Contract end date is after the contractor\'s agreement end date.']);
            }
        }
    }

    /**
     * FSD 10.3.2 — "Same as Current Address" copies current address fields
     * into the permanent-address columns.
     */
    private function applySameAsCurrentAddress(Request $request, array $data): array
    {
        if ($request->boolean('same_as_current_address')) {
            $data['permanent_address_line1'] = $data['address_line1'] ?? null;
            $data['permanent_address_line2'] = $data['address_line2'] ?? null;
            $data['permanent_city']          = $data['city'] ?? null;
            $data['permanent_district']      = $data['district'] ?? null;
            $data['permanent_state']         = $data['state'] ?? null;
            $data['permanent_pincode']       = $data['pincode'] ?? null;
        }

        return $data;
    }

    /**
     * FSD 10.3.3 — "Employee-specific overrides shall require proper
     * permission." Only a user with rule_engine.full may set a per-employee
     * Rule Engine override; silently stripped otherwise so a crafted request
     * can't smuggle one in.
     */
    private function stripUnauthorizedRuleOverrides(array $data): array
    {
        if (! auth()->user()->can('rule_engine.full')) {
            unset($data['weekly_off_rule_id'], $data['attendance_rule_id'], $data['payroll_rule_id']);
        }

        return $data;
    }

    /** FSD 10.8 — "Contractor Rate ... Permission-controlled." */
    private function stripUnauthorizedContractorRate(array $data): array
    {
        if (! auth()->user()->can('employees.full')) {
            unset($data['contractor_rate']);
        }

        return $data;
    }

    /**
     * Employee Information — Basic Salary auto-matches a Salary Slab by
     * range, scoped to the given branch (ranges are per-branch, see
     * SalarySlabController::assertNoRangeOverlap()). Since ranges never
     * overlap within a branch, at most one slab can ever match.
     */
    private function matchSalarySlab(int $branchId, float $basicSalary): ?\App\Models\SalarySlab
    {
        return \App\Models\SalarySlab::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('salary_from', '<=', $basicSalary)
            ->where('salary_to', '>=', $basicSalary)
            ->first();
    }

    /**
     * Pulls basic_salary/salary_earnings out of the wizard payload (they're
     * not Employee columns) and, once the employee is saved, resolves the
     * matching Salary Slab and stamps a fresh EmployeeSalaryStructure +
     * EmployeeSalaryComponent rows from it. Only earnings the user marked
     * "Yes" (present in salary_earnings) are kept — "No" ones are excluded
     * entirely, both from the employee's salary structure and (so payroll
     * generation never picks them up) from calculated_amount altogether.
     */
    private function extractSalaryData(array &$data): array
    {
        $basicSalary = (float) ($data['basic_salary'] ?? 0);
        $selectedEarningIds = array_map('intval', $data['salary_earnings'] ?? []);
        unset($data['basic_salary'], $data['salary_earnings']);

        return [$basicSalary, $selectedEarningIds];
    }

    private function applySalaryStructure(Employee $employee, float $basicSalary, array $selectedEarningIds): void
    {
        $slab = $this->matchSalarySlab($employee->branch_id, $basicSalary);
        if (! $slab) {
            // Basic Salary was already validated against a matching slab
            // before this point (assertBasicSalaryMatchesSlab()) — this is
            // just a defensive no-op if that invariant somehow doesn't hold.
            return;
        }

        $structureData = [
            'employee_id'       => $employee->id,
            'salary_slab_id'    => $slab->id,
            'ctc'               => $slab->ctc($basicSalary),
            'basic_salary'      => $basicSalary,
            'hra' => 0, 'da' => 0, 'ta' => 0, 'medical_allowance' => 0, 'special_allowance' => 0,
            'gross_salary'      => $slab->grossSalary($basicSalary),
            'pf_employee'       => $slab->pfEmployee($basicSalary),
            'pf_employer'       => $slab->pfEmployer($basicSalary),
            'esi_employee'      => $slab->esiEmployee($basicSalary),
            'esi_employer'      => $slab->esiEmployer($basicSalary),
            'tds'               => $slab->tds($basicSalary),
            'net_salary'        => $slab->netSalary($basicSalary),
            'effective_from'    => now()->toDateString(),
            'is_current'        => true,
            'created_by'        => auth()->id(),
        ];

        $employee->salaryHistory()->update(['is_current' => false, 'effective_to' => now()->toDateString()]);
        $structure = $employee->salaryHistory()->create($structureData);

        foreach ($slab->earningsComponents as $slabComponent) {
            if (! in_array($slabComponent->component_id, $selectedEarningIds, true)) {
                continue;
            }
            $amount = round($basicSalary * (float) $slabComponent->rate / 100, 2);
            $structure->components()->create([
                'component_type'    => 'earning',
                'component_id'      => $slabComponent->component_id,
                'component_name'    => $slabComponent->component_name,
                'calculation_type'  => $slabComponent->calculation_type,
                'rate'              => $slabComponent->rate,
                'calculated_amount' => $amount,
            ]);
        }
    }

    /**
     * Part 3's "no match -> block" rule — must run against validated data
     * before the employee is written, using $branchId the same way
     * BranchScope::stampBranchId() will resolve it moments later.
     */
    private function assertBasicSalaryMatchesSlab(int $branchId, float $basicSalary): void
    {
        if (! $this->matchSalarySlab($branchId, $basicSalary)) {
            throw ValidationException::withMessages([
                'basic_salary' => 'No salary slab found for this amount. Adjust the Basic Salary or ask an admin to create a slab covering this range.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['designation_id'] = $this->resolveDesignationId($data['designation'] ?? null);
        unset($data['designation']);
        $data['created_by'] = auth()->id();
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id']);
        $this->assertBasicSalaryMatchesSlab($data['branch_id'], (float) $data['basic_salary']);

        $this->assertContractorForLabour($data);
        $data = $this->applySameAsCurrentAddress($request, $data);
        $data = $this->stripUnauthorizedRuleOverrides($data);
        $data = $this->stripUnauthorizedContractorRate($data);
        [$basicSalary, $selectedEarningIds] = $this->extractSalaryData($data);

        $bankData = $this->extractBankData($data);
        $data['is_pf_applicable'] = $data['is_pf_applicable'] === 'yes';
        $data['is_esi_applicable'] = $data['is_esi_applicable'] === 'yes';
        $data['is_tds_applicable'] = $data['is_tds_applicable'] === 'yes';
        $data['is_earnings_applicable'] = $data['is_earnings_applicable'] === 'yes';
        $data['is_ot_applicable'] = $data['is_ot_applicable'] === 'yes';

        // Employee Code is always auto-generated right here at creation.
        $primaryType = $data['employee_category'] === 'staff' ? 'staff' : 'labour';
        $labourType = self::LABOUR_TYPE_MAP[$data['employee_category']];
        $data['primary_employee_type'] = $primaryType;
        $data['labour_type'] = $labourType;
        $data['employee_code'] = $this->resolveEmployeeCode($data, $primaryType, $labourType);
        $codeWasGenerated = true;
        unset($data['employee_category']);

        $this->assertDepartmentBelongsToBranch($data['department_id'] ?? null, $data['branch_id']);
        $this->assertDesignationBelongsToBranch($data['designation_id'] ?? null, $data['branch_id']);
        $this->assertDepartmentIsActive($data['department_id'] ?? null);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
        }

        if (empty($data['display_name'])) {
            $data['display_name'] = trim(collect([$data['first_name'] ?? null, $data['middle_name'] ?? null, $data['last_name'] ?? null])->filter()->implode(' '));
        }

        $employee = $this->createEmployeeWithRetry($data, $request, $codeWasGenerated, $primaryType, $labourType);

        if ($bankData) {
            $employee->bankDetails()->create($bankData);
        }

        $this->applySalaryStructure($employee, $basicSalary, $selectedEarningIds);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Bank Details (step 4) live on employee_bank_details, not employees —
     * pull them out of the validated payload before Employee::create()/update().
     * Not shown/submitted at all for Contract Labour, and only actually
     * saved if at least one bank field was filled in.
     */
    private function extractBankData(array &$data): ?array
    {
        $fields = ['bank_id', 'bank_name', 'account_holder_name', 'account_number', 'ifsc_code', 'branch_name', 'account_type'];
        $bankData = [];
        foreach ($fields as $field) {
            if (! empty($data[$field])) {
                $bankData[$field] = $data[$field];
            }
            unset($data[$field]);
        }
        unset($data['account_number_confirmation']);

        if (($data['employee_category'] ?? null) === 'contract_labour') {
            return null;
        }

        return $bankData ?: null;
    }

    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['branch', 'department', 'designation', 'contractor', 'designationContractor',
            'shift', 'user', 'currentSalary', 'exitRecord',
            'weeklyOffRuleOverride', 'attendanceRuleOverride', 'payrollRuleOverride']);

        // Module 11 (FSD 15.2) — statutory ID unmasking is gated by the
        // dedicated View Sensitive Data permission, not the coarse `full`
        // access level (previously any user with Edit/Delete access to
        // Employees could also see unmasked statutory IDs, which conflated
        // two unrelated permissions). Bank Details moved to the Employee
        // Slab screen — this view no longer shows/edits them at all.
        $canViewFullBankDetails = \App\Support\SensitiveDataAccess::canView('employees');

        return view('employees.show', compact('employee', 'canViewFullBankDetails'));
    }

    public function edit(Employee $employee, Request $request)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $activeTab = (int) $request->input('tab', 2);
        $employee->load('bankDetails', 'currentSalary.components');
        $primaryBankDetail = $employee->bankDetails->firstWhere('is_primary', true) ?? $employee->bankDetails->first();

        return view('employees.edit', array_merge(
            compact('employee', 'activeTab', 'primaryBankDetail'),
            $this->formData($employee)
        ));
    }

    public function update(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        // Only the Employee Slab's Employment Information form submits
        // 5-step wizard — every step is always submitted together on Edit,
        // same rules as Create.
        $data = $request->validate($this->rules($employee->id));
        $data['designation_id'] = $this->resolveDesignationId($data['designation'] ?? null);
        unset($data['designation']);
        if (empty($data['employee_code'])) {
            // employee_code is never submitted (read-only on Edit) — always
            // auto-generated at creation and never blanked out here.
            unset($data['employee_code']);
        }
        // A branch-scoped actor can never move an employee to another branch,
        // even via a crafted request — branch_id is always re-forced here,
        // exactly as on create.
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        $this->assertDepartmentBelongsToBranch($data['department_id'], $data['branch_id']);
        $this->assertDesignationBelongsToBranch($data['designation_id'] ?? null, $data['branch_id']);
        $this->assertDepartmentIsActive($data['department_id'], $employee);
        $this->assertContractorForLabour($data);
        $this->assertReportingToIsNotSelf($data['reporting_to'] ?? null, $employee->id);
        $this->assertBasicSalaryMatchesSlab($data['branch_id'], (float) $data['basic_salary']);
        $data = $this->applySameAsCurrentAddress($request, $data);
        $data = $this->stripUnauthorizedRuleOverrides($data);
        $data = $this->stripUnauthorizedContractorRate($data);
        [$basicSalary, $selectedEarningIds] = $this->extractSalaryData($data);

        $bankData = $this->extractBankData($data);
        $data['is_pf_applicable'] = $data['is_pf_applicable'] === 'yes';
        $data['is_esi_applicable'] = $data['is_esi_applicable'] === 'yes';
        $data['is_tds_applicable'] = $data['is_tds_applicable'] === 'yes';
        $data['is_earnings_applicable'] = $data['is_earnings_applicable'] === 'yes';
        $data['is_ot_applicable'] = $data['is_ot_applicable'] === 'yes';

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
        }

        $category = $data['employee_category'];
        $primaryType = $category === 'staff' ? 'staff' : 'labour';
        $labourType = self::LABOUR_TYPE_MAP[$category];
        $data['primary_employee_type'] = $primaryType;
        $data['labour_type'] = $labourType;

        // Employee Code is generated exactly once — the first time this
        // employee's Employment Information (step 4) is saved. Once set,
        // it's never regenerated on later saves.
        $codeWasGenerated = false;
        if (empty($employee->employee_code)) {
            $data['employee_code'] = $this->resolveEmployeeCode($data, $primaryType, $labourType);
            $codeWasGenerated = true;
        }
        unset($data['employee_category']);

        $this->updateEmployeeWithRetry($employee, $data, $codeWasGenerated, $primaryType, $labourType);
        $this->applySalaryStructure($employee, $basicSalary, $selectedEarningIds);

        if ($bankData) {
            $primary = $employee->bankDetails()->where('is_primary', true)->first();
            if ($primary) {
                $primary->update($bankData);
            } else {
                $employee->bankDetails()->create($bankData + ['is_primary' => true]);
            }
        }

        if ($request->boolean('create_user') && $employee->official_email && ! $employee->user) {
            User::create([
                'name'        => $this->generateUniqueDisplayName($employee->full_name),
                'username'    => $this->generateUniqueUsername($employee->official_email),
                'email'       => $employee->official_email,
                'password'    => Hash::make('Welcome@' . now()->year),
                'role_id'     => $request->input('role_id', 2),
                'employee_id' => $employee->id,
                'is_active'   => true,
            ]);
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        // Module 11 (FSD 15.2) — "Delete permission shall be restricted."
        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'employees', 'delete')) {
            abort(403, 'You do not have the "Delete" permission for Employees in Branch Administration.');
        }

        $employee->update(['status' => 'terminated']);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee removed.');
    }

    public function documents(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $documents = $employee->documents()->latest()->get();
        $mandatoryTypes = json_decode(Setting::get('employee', 'mandatory_document_types', '[]'), true) ?: [];
        $missingMandatory = array_diff($mandatoryTypes, $documents->pluck('document_type')->all());
        return view('employees.documents', compact('employee', 'documents', 'missingMandatory'));
    }

    private const DOCUMENT_TYPES = 'aadhaar,pan,bank_proof,appointment_letter,employment_agreement,education_certificate,experience_certificate,contractor_id,passport,other';

    /**
     * FSD Tab 10 — "Employee Documents must support uploading multiple
     * documents using an 'Add Document' table." Accepts either the classic
     * single-file submission (document_type/file/...) or a `documents[]`
     * array (one row per Add-Document table row), so the existing
     * single-upload callers keep working unchanged.
     */
    public function uploadDocument(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        $rows = $this->documentRows($request);
        $count = $this->saveDocumentRows($employee, $rows);

        return $this->redirectAfterTabAction($employee, $request, $count > 1 ? $count . ' documents uploaded.' : 'Document uploaded.');
    }

    /** Validates either the classic single-file submission or a `documents[]` array (one row per Add-Document table row). */
    private function documentRows(Request $request): array
    {
        return $request->has('documents')
            ? $request->validate([
                'documents'                   => ['required', 'array', 'min:1'],
                'documents.*.document_type'   => ['required', 'in:' . self::DOCUMENT_TYPES],
                'documents.*.document_number' => ['nullable', 'string', 'max:100'],
                'documents.*.issue_date'      => ['nullable', 'date'],
                'documents.*.expiry_date'     => ['nullable', 'date'],
                'documents.*.remarks'         => ['nullable', 'string', 'max:255'],
                'documents.*.file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ])['documents']
            : [$request->validate([
                'document_type'   => ['required', 'in:' . self::DOCUMENT_TYPES],
                'document_number' => ['nullable', 'string', 'max:100'],
                'issue_date'      => ['nullable', 'date'],
                'expiry_date'     => ['nullable', 'date'],
                'remarks'         => ['nullable', 'string', 'max:255'],
                'file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ])];
    }

    /** Shared by the standalone Tab 10 route and the unified Create submission. */
    private function saveDocumentRows(Employee $employee, array $rows): int
    {
        foreach ($rows as $row) {
            $file = $row['file'];
            $path = $file->store('employee-documents/' . $employee->id, 'public');

            // Fixes a previously-broken insert: document_name/file_size/file_type/
            // uploaded_by are NOT NULL columns the controller never populated,
            // and document_number/expiry_date/is_verified now exist on the table
            // (2026_07_17_000004 migration) matching what this always tried to save.
            $employee->documents()->create([
                'document_type'   => $row['document_type'],
                'document_name'   => $file->getClientOriginalName(),
                'document_number' => $row['document_number'] ?? null,
                'issue_date'      => $row['issue_date'] ?? null,
                'file_path'       => $path,
                'file_size'       => $file->getSize(),
                'file_type'       => $file->getClientMimeType(),
                'expiry_date'     => $row['expiry_date'] ?? null,
                'remarks'         => $row['remarks'] ?? null,
                'uploaded_by'     => auth()->id(),
            ]);
        }

        return count($rows);
    }

    public function deleteDocument(Employee $employee, \App\Models\EmployeeDocument $document)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        abort_if($document->employee_id !== $employee->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    // ── Bank Details (FSD 10.3.5) ────────────────────────────────────────

    private function bankDetailRules(): array
    {
        return [
            'bank_id'              => ['nullable', 'exists:banks,id'],
            'bank_name'            => ['nullable', 'string', 'max:100'],
            'account_holder_name'  => ['nullable', 'string', 'max:100'],
            'account_number'       => ['nullable', 'string', 'max:50'],
            'account_number_confirmation' => ['nullable', 'same:account_number'],
            'ifsc_code'            => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'branch_name'          => ['nullable', 'string', 'max:100'],
            'account_type'         => ['nullable', 'in:savings,current,salary'],
            'is_primary'           => ['boolean'],
        ];
    }

    private const FIELD_REFERENCING_RULES = [
        'same', 'different', 'confirmed',
        'required_if', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all',
        'after', 'after_or_equal', 'before', 'before_or_equal', 'gt', 'gte', 'lt', 'lte',
    ];

    /**
     * Nests a rule set under $prefix (e.g. 'bank_details.bank_name'
     * instead of 'bank_name') so the SAME rule set defined for a
     * standalone Tab 8/9 route can validate its nested `bank_details[...]`/
     * `salary[...]` input when submitted together with Tabs 1-7 on Create.
     * Rules that reference ANOTHER field by name (same:x, required_if:x,...,
     * after:x) have that referenced field name prefixed too, since Laravel
     * resolves it against the request's real (nested) field name, not the
     * unprefixed one this rule set was originally written against.
     */
    private function prefixRules(string $prefix, array $rules): array
    {
        $prefixed = [];
        foreach ($rules as $field => $fieldRules) {
            $prefixed["$prefix.$field"] = array_map(function ($rule) use ($prefix) {
                if (! is_string($rule) || ! str_contains($rule, ':')) {
                    return $rule;
                }
                [$name, $params] = explode(':', $rule, 2);
                if (! in_array($name, self::FIELD_REFERENCING_RULES, true)) {
                    return $rule;
                }
                $parts = explode(',', $params);
                $parts[0] = "$prefix.{$parts[0]}";
                return $name . ':' . implode(',', $parts);
            }, $fieldRules);
        }

        return $prefixed;
    }

    /**
     * Tabs 8-10 (Bank/Salary/Documents) each keep their own existing route,
     * embedded as further tabs in the same registration wizard shell — a
     * "Save & Next" button on one of these tabs submits to next_tab so the
     * wizard advances; without it (e.g. a plain "Save"/delete action) the
     * request just returns to the referring page, same as before.
     */
    private function redirectAfterTabAction(Employee $employee, Request $request, string $message)
    {
        if ($request->filled('next_tab')) {
            return redirect()->route('employees.edit', ['employee' => $employee, 'tab' => $request->input('next_tab')])
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }

    public function storeBankDetail(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $data = $request->validate($this->bankDetailRules(), [
            'ifsc_code.regex' => 'The IFSC code format is invalid (e.g. HDFC0001234).',
            'account_number_confirmation.same' => 'Account Number and Confirm Account Number must match.',
        ]);
        unset($data['account_number_confirmation']);
        $data['employee_id'] = $employee->id;

        if ($request->boolean('is_primary')) {
            $employee->bankDetails()->update(['is_primary' => false]);
        }

        $employee->bankDetails()->create($data);

        return $this->redirectAfterTabAction($employee, $request, 'Bank details added.');
    }

    public function updateBankDetail(Request $request, Employee $employee, EmployeeBankDetail $bankDetail)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        abort_if($bankDetail->employee_id !== $employee->id, 404);

        $data = $request->validate($this->bankDetailRules(), [
            'ifsc_code.regex' => 'The IFSC code format is invalid (e.g. HDFC0001234).',
            'account_number_confirmation.same' => 'Account Number and Confirm Account Number must match.',
        ]);
        unset($data['account_number_confirmation']);

        if ($request->boolean('is_primary')) {
            $employee->bankDetails()->where('id', '!=', $bankDetail->id)->update(['is_primary' => false]);
        }

        $bankDetail->update($data);

        return $this->redirectAfterTabAction($employee, $request, 'Bank details updated.');
    }

    public function destroyBankDetail(Employee $employee, EmployeeBankDetail $bankDetail)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        abort_if($bankDetail->employee_id !== $employee->id, 404);

        $bankDetail->delete();

        return back()->with('success', 'Bank details removed.');
    }

    /** Superseded by the Designation & Salary tab in the Edit wizard (Salary Slab-driven) — kept as a redirect so no old link/bookmark 404s. */
    public function salary(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        return redirect()->route('employees.edit', ['employee' => $employee, 'tab' => 9]);
    }

    /**
     * Employee Information — "enter Basic Salary, auto-find the matching
     * Salary Slab and show its earnings" live lookup. Scoped to the
     * currently effective branch (ranges are per-branch) — never trusts a
     * client-supplied slab id, only the basic_salary figure and the
     * server-resolved branch.
     */
    public function salarySlabBreakdown(Request $request)
    {
        $basicSalary = (float) $request->input('basic_salary', 0);
        $branchId = BranchScope::currentBranchId() ?? (int) $request->input('branch_id');

        $slab = $this->matchSalarySlab($branchId, $basicSalary);
        if (! $slab) {
            return response()->json(['matched' => false]);
        }

        return response()->json([
            'matched' => true,
            'slab' => ['id' => $slab->id, 'name' => $slab->name],
            'earnings' => $slab->earningsComponents->map(fn ($c) => [
                'component_id' => $c->component_id,
                'name' => $c->component_name,
                'rate' => (float) $c->rate,
                'amount' => round($basicSalary * (float) $c->rate / 100, 2),
            ]),
        ] + $this->buildSalarySlabBreakdown($slab, $basicSalary));
    }

    /**
     * Single builder for the read-only salary breakdown shown in the
     * Designation & Salary tab — used both by the live AJAX endpoint above
     * and by the Edit page's initial render. Basic Salary is entered
     * per-employee (not stored on the Salary Slab), so it's always passed
     * in rather than read off the slab.
     */
    /** Public — also called by EmployeeSlabController for its single-step Designation & Salary section. */
    public function buildSalarySlabBreakdown(\App\Models\SalarySlab $salarySlab, float $basicSalary = 0): array
    {
        return [
            'basic_salary'    => $basicSalary,
            'gross_salary'    => $salarySlab->grossSalary($basicSalary),
            'total_deductions' => $salarySlab->totalDeductions($basicSalary),
            'net_salary'      => $salarySlab->netSalary($basicSalary),
            'ctc'             => $salarySlab->ctc($basicSalary),
            'employer_contributions' => $salarySlab->employerContributions($basicSalary),
            'pf_employee'  => $salarySlab->pfEmployee($basicSalary),
            'pf_employer'  => $salarySlab->pfEmployer($basicSalary),
            'esi_employee' => $salarySlab->esiEmployee($basicSalary),
            'esi_employer' => $salarySlab->esiEmployer($basicSalary),
            'tds'          => $salarySlab->tds($basicSalary),
            'pf_employee_percentage'  => (float) $salarySlab->pf_employee_percentage,
            'pf_employer_percentage'  => (float) $salarySlab->pf_employer_percentage,
            'esi_employee_percentage' => (float) $salarySlab->esi_employee_percentage,
            'esi_employer_percentage' => (float) $salarySlab->esi_employer_percentage,
            'tds_percentage'          => (float) $salarySlab->tds_percentage,
        ];
    }

    public function storeSalary(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'employees', 'view_sensitive')) {
            abort(403, 'You do not have the "View Sensitive Data" permission for Employees in Branch Administration.');
        }

        $data = $request->validate($this->salaryRules());
        $this->saveSalaryStructure($employee, $data);

        // PF/ESI/TDS/OT applicability checkboxes live on this same
        // Designation & Salary section — validated separately from
        // salaryRules() since this is its own standalone form/route.
        // Biometric ID itself is captured on Step 1 (Personal Information)
        // and never resubmitted here (People Module Update) — it must NOT
        // be required again, or this section can never be saved without
        // re-entering it a second time.
        $extra = $request->validate($this->employeeTabExtraRules());
        $employee->update($extra);

        return $this->redirectAfterTabAction($employee, $request, 'Salary structure saved.');
    }

    /**
     * PF/ESI/TDS/OT applicability checkboxes, saved from the Designation &
     * Salary section. Validated separately from salaryRules() since this is
     * its own standalone form/route.
     */
    private function employeeTabExtraRules(): array
    {
        return [
            'is_pf_applicable'  => ['boolean'],
            'is_esi_applicable' => ['boolean'],
            'is_tds_applicable' => ['boolean'],
            'is_ot_applicable'  => ['boolean'],
        ];
    }

    /**
     * FSD — "The Salary Slab should become the single source of truth for
     * employee salary calculations... no duplicate or manual salary
     * calculations should exist in the Employee module." Selecting a
     * Salary Slab is now the ONLY salary input; CTC/Basic/HRA/DA/TA/
     * Medical/Special Allowance/Salary Components are no longer accepted
     * from this form at all — they're always resolved fresh from the
     * selected slab in saveSalaryStructure() below.
     */
    private function salaryRules(): array
    {
        return [
            'salary_slab_id'    => ['required', 'exists:salary_slabs,id'],
            'basic_salary'      => ['required', 'numeric', 'min:0'],
            'effective_from'    => ['required', 'date'],
            'effective_to'      => ['nullable', 'date', 'after:effective_from'],
            // Employee Master — "Designation & Salary" section. Department/
            // Designation are shared with Tab 5's own fields (same employee
            // columns); Employee Category/Type/Contractor Name are this
            // section's own classification, distinct from Employee Category
            // on Tab 5.
            'department_id'                  => ['nullable', 'exists:departments,id'],
            // Free text, resolved-or-created into designation_id in
            // saveSalaryStructure() below — same as Tab 5's own field.
            'designation'                    => ['nullable', 'string', 'max:100'],
            'designation_employee_category'  => ['required', 'in:company,contractor'],
            'designation_employee_type'      => ['required', 'in:staff,labor,contractor_staff,contractor_labor'],
            'designation_contractor_id'      => ['required_if:designation_employee_category,contractor', 'nullable', 'exists:contractors,id'],
        ];
    }

    /**
     * Employee Master — "Designation & Salary" Type options are scoped to
     * the selected Employee Category (Company -> Staff/Labor, Contractor ->
     * Contractor Staff/Contractor Labor); a mismatched combination (possible
     * via a crafted request, since the client only hides the invalid
     * options) is rejected server-side too.
     */
    private function assertDesignationTypeMatchesCategory(string $category, string $type): void
    {
        $validTypes = [
            'company'    => ['staff', 'labor'],
            'contractor' => ['contractor_staff', 'contractor_labor'],
        ];

        if (! in_array($type, $validTypes[$category] ?? [], true)) {
            throw ValidationException::withMessages([
                'designation_employee_type' => 'The selected Type does not match the selected Employee Category.',
            ]);
        }
    }

    /**
     * Shared by the standalone Tab 9 route and the unified Create submission.
     * FSD — "The employee should inherit the complete salary structure from
     * the selected Salary Slab." Every salary figure here is read fresh from
     * the slab's own computed accessors; nothing is accepted from the
     * request except WHICH slab and the effective dates.
     */
    private function saveSalaryStructure(Employee $employee, array $data): void
    {
        $this->assertDesignationTypeMatchesCategory($data['designation_employee_category'], $data['designation_employee_type']);

        $employeeUpdates = [
            'designation_employee_category' => $data['designation_employee_category'],
            'designation_employee_type'     => $data['designation_employee_type'],
            'designation_contractor_id'     => $data['designation_employee_category'] === 'contractor'
                ? ($data['designation_contractor_id'] ?? null)
                : null,
        ];
        if (! empty($data['department_id'])) {
            $employeeUpdates['department_id'] = $data['department_id'];
        }
        if (! empty($data['designation'])) {
            $employeeUpdates['designation_id'] = $this->resolveDesignationId($data['designation']);
        }
        $employee->update($employeeUpdates);

        $slab = \App\Models\SalarySlab::findOrFail($data['salary_slab_id']);
        $basicSalary = (float) $data['basic_salary'];

        $structureData = [
            'employee_id'       => $employee->id,
            'salary_slab_id'    => $slab->id,
            'ctc'               => $slab->ctc($basicSalary),
            'basic_salary'      => $basicSalary,
            'hra'               => 0,
            'da'                => 0,
            'ta'                => 0,
            'medical_allowance' => 0,
            'special_allowance' => 0,
            'gross_salary'      => $slab->grossSalary($basicSalary),
            'pf_employee'       => $slab->pfEmployee($basicSalary),
            'pf_employer'       => $slab->pfEmployer($basicSalary),
            'esi_employee'      => $slab->esiEmployee($basicSalary),
            'esi_employer'      => $slab->esiEmployer($basicSalary),
            'tds'               => $slab->tds($basicSalary),
            'net_salary'        => $slab->netSalary($basicSalary),
            'effective_from'    => $data['effective_from'],
            'effective_to'      => $data['effective_to'] ?? null,
            'created_by'        => auth()->id(),
        ];

        $employee->salaryHistory()->update(['is_current' => false, 'effective_to' => now()->toDateString()]);
        $employee->salaryHistory()->create(array_merge($structureData, ['is_current' => true]));
    }

    public function exit(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        return view('employees.exit', compact('employee'));
    }

    public function processExit(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $data = $request->validate([
            'exit_type'         => ['required', 'in:resignation,termination,retirement,absconding'],
            'resignation_date'  => ['required', 'date'],
            'last_working_date' => ['required', 'date'],
            'exit_reason'       => ['required', 'string'],
            'remarks'           => ['nullable', 'string'],
        ]);
        $data['approved_by'] = auth()->id();

        $employee->exitRecord()->updateOrCreate(['employee_id' => $employee->id], $data);
        $employee->update(['status' => 'terminated']);

        return redirect()->route('employees.index')->with('success', 'Employee exit processed.');
    }

    /**
     * A department belongs to a branch (departments.branch_id) — an employee
     * can never be filed under another branch's department, whether the
     * mismatch comes from a branch-scoped actor or a tampered request.
     */
    private function assertDepartmentBelongsToBranch(?int $departmentId, ?int $branchId): void
    {
        if ($departmentId === null || $branchId === null) {
            return;
        }

        $department = Department::find($departmentId);

        if ($department && (int) $department->branch_id !== (int) $branchId) {
            throw ValidationException::withMessages([
                'department_id' => 'The selected department does not belong to the selected branch.',
            ]);
        }
    }

    /**
     * Server-side guard mirroring assertDepartmentBelongsToBranch() — a
     * Designation has no branch_id of its own (reached transitively via
     * department_id), so a crafted request could otherwise submit a
     * designation whose department belongs to a different branch than the
     * dropdown (scoped to the current branch) would ever offer.
     */
    private function assertDesignationBelongsToBranch(?int $designationId, ?int $branchId): void
    {
        if ($designationId === null || $branchId === null) {
            return;
        }

        $designation = Designation::find($designationId);

        if ($designation && $designation->department_id !== null && (int) $designation->department?->branch_id !== (int) $branchId) {
            throw ValidationException::withMessages([
                // 'designation' (not 'designation_id') — that's the free-text
                // field name the form actually shows @error() next to now.
                'designation' => 'A designation with this exact name already exists under a different branch\'s department. Please use a different name.',
            ]);
        }
    }

    /**
     * Designation is no longer picked from an existing Masters record — the
     * user types the name directly on the Employee form. This resolves it to
     * (creating if necessary) a Designation row with that exact name, so the
     * `designation_id` FK, the Designation Master table, and every existing
     * report/payslip/view that reads $employee->designation->name keep
     * working completely unchanged.
     */
    private function resolveDesignationId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Designation::firstOrCreate(['name' => $name], ['is_active' => true])->id;
    }

    /** Public — also called by EmployeeSlabController for its single-step form's reference data. */
    public function formData(?Employee $employee = null): array
    {
        $currentBranchId = BranchScope::currentBranchId();

        // The Branch field is always the currently active branch (Branch
        // Switcher for Super Admin, own branch for a branch-scoped actor) —
        // store()/update() force it to this same branch via
        // BranchScope::stampBranchId() regardless of what's submitted, so it
        // is shown read-only rather than as a selectable dropdown.
        $currentBranch = $currentBranchId ? Branch::find($currentBranchId) : $employee?->branch;

        return [
            'currentBranch' => $currentBranch,
            // Inactive departments are excluded from new-employee assignment
            // (FSD 7.1); an employee's own already-assigned department is kept
            // in the list on the edit form even if it has since gone inactive,
            // so the field doesn't silently render blank.
            'departments'   => BranchScope::scopeQuery(Department::query())
                ->where(fn($q) => $q->where('is_active', true)->when($employee?->department_id, fn($q2) => $q2->orWhere('id', $employee->department_id)))
                ->orderBy('name')->get(),
            'employeeTypes' => EmployeeType::where('is_active', true)->get(),
            'contractors'   => BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get(),
            // Scoped to the employee's own branch — a Shift now belongs to
            // exactly one branch (see ShiftController), so only shifts
            // created under this employee's branch are assignable. The
            // employee's own already-assigned shift is kept in the list on
            // the edit form even if it's since gone inactive or belongs to
            // a different branch, so the field doesn't silently render blank.
            'shifts'        => BranchScope::scopeQuery(Shift::query())
                ->where(fn($q) => $q->where('is_active', true)->when($employee?->shift_id, fn($q2) => $q2->orWhere('id', $employee->shift_id)))
                ->get(),
            'managers'      => BranchScope::scopeQuery(Employee::active())->orderBy('first_name')->get(),
            'roles'         => \App\Models\Role::orderBy('name')->get(),
            'banks'         => BranchScope::scopeQuery(Bank::where('is_active', true))->orderBy('name')->get(),
            'canOverrideRules' => auth()->user()->can('rule_engine.full'),
            'canSetContractorRate' => auth()->user()->can('employees.full'),
            'canViewFullBankDetails' => \App\Support\SensitiveDataAccess::canView('employees'),
            'ruleOptions'   => auth()->user()->can('rule_engine.full') ? $this->ruleOptions() : [],
            'states'        => config('states', []),
        ];
    }

    /**
     * Active BusinessRule options for the per-employee override selects
     * (FSD 10.3.3). `weekly_off`/`attendance` map 1:1 to their own select;
     * every other payroll-related category is grouped under `payroll` since
     * `payroll_rule_id` is a single reference field (see BusinessRule::resolveForEmployee()).
     */
    private function ruleOptions(): array
    {
        $rules = BusinessRule::where('status', 'active')->orderBy('name')->get();

        return [
            'weekly_off' => $rules->where('category', 'weekly_off')->values(),
            'attendance' => $rules->where('category', 'attendance')->values(),
            'payroll'    => $rules->whereIn('category', ['lop', 'pf', 'esi', 'tds', 'overtime'])->values(),
        ];
    }

    /**
     * 5-step Employee wizard (Personal/Contact/Address/Employee Information/
     * Statutory Details) — Employment Information fields (Department,
     * Designation, Employee Category, Date of Joining, Shift) are always
     * collected on step 4, so they're always required (no more Create-vs-
     * Employee-Slab nullable split).
     */
    private function rules(int $excludeId = 0): array
    {
        $minAge = (int) Setting::get('employee', 'min_working_age', 18);
        $nameRegex = 'regex:/^[a-zA-Z .\'-]+$/';
        $classificationRequired = 'required';

        $rules = [
            'first_name'       => ['required', 'string', 'max:100', $nameRegex],
            'middle_name'      => ['nullable', 'string', 'max:100', $nameRegex],
            'last_name'        => ['nullable', 'string', 'max:100', $nameRegex],
            'display_name'     => ['nullable', 'string', 'max:200'],
            // Nullable here even though the column is NOT NULL — Employee
            // Code is now always auto-generated server-side (a configured
            // Employee Number Rule, or else the branch-wise default
            // sequence); store() re-checks for emptiness as a safety net.
            // Uniqueness is scoped per branch (not global) since each
            // branch runs its own independent numbering sequence — the same
            // code string can legitimately exist in two different branches.
            // Uses the same branch_id precedence as BranchScope::stampBranchId()
            // (the currently selected branch overrides whatever the form
            // submitted) so the check matches what will actually be saved.
            // whereNull('deleted_at') is load-bearing: Rule::unique() has no
            // built-in awareness of soft deletes — without it, a deleted
            // employee's code stays permanently "taken" and can never be
            // reused within that branch.
            'employee_code'    => [
                'nullable', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')
                    ->where(fn ($query) => $query->where('branch_id', BranchScope::currentBranchId() ?? request()->input('branch_id')))
                    ->whereNull('deleted_at')
                    ->ignore($excludeId),
            ],
            // Employment Information — step 4 of the 5-step wizard.
            'employee_category' => [$classificationRequired, 'in:staff,company_labour,contract_labour'],
            'branch_id'        => ['required', 'exists:branches,id'],
            'department_id'    => [$classificationRequired, 'exists:departments,id'],
            // Designation is no longer picked from the Masters module — the
            // user types it directly; resolveDesignationId() resolves it to
            // (or creates) the matching Designation record right after
            // validation, in both store() and update().
            'designation'      => [$classificationRequired, 'string', 'max:100'],
            // Employee Type dropdown removed from the Employee Slab form
            // (and never collected in Create Employee either) — always
            // nullable now; employees.employee_type_id stays in the schema,
            // just no longer set from any form.
            'employee_type_id' => ['nullable', 'exists:employee_types,id'],
            'date_of_joining'  => ['required', 'date'],
            'date_of_confirmation' => ['nullable', 'date', 'after_or_equal:date_of_joining'],
            'probation_end_date'   => ['nullable', 'date', 'after:date_of_joining'],
            'contract_start_date'  => ['required_if:employee_category,contract_labour', 'nullable', 'date'],
            'contract_end_date'    => ['nullable', 'date', 'after:contract_start_date'],
            'date_of_birth'    => ['required', 'date', 'before:today', 'before_or_equal:' . now()->subYears($minAge)->toDateString()],
            'gender'           => ['required', 'in:male,female,other'],
            'marital_status'   => ['nullable', 'in:single,married,divorced,widowed'],
            'blood_group'      => ['nullable', 'string', 'max:5'],
            'father_spouse_name' => ['nullable', 'string', 'max:150', $nameRegex],
            'nationality'      => ['nullable', 'string', 'max:50'],
            'religion'         => ['nullable', 'string', 'max:50'],
            'personal_email'   => ['nullable', 'email', 'max:150'],
            // Scoped per branch (not global), matching employee_code/
            // biometric_number above — two different branches may
            // legitimately onboard an employee under the same official
            // email pattern (e.g. a shared naming convention).
            'official_email'   => [
                'required', 'email', 'max:255',
                Rule::unique('employees', 'official_email')
                    ->where(fn ($query) => $query->where('branch_id', BranchScope::currentBranchId() ?? request()->input('branch_id')))
                    ->whereNull('deleted_at')
                    ->ignore($excludeId),
            ],
            'phone'            => ['required', 'digits:10'],
            'alternate_phone'  => ['nullable', 'digits:10'],
            // Status isn't one of the 5 wizard steps — store()/update() default
            // it to 'active' when not present.
            'status'           => ['nullable', 'in:active,inactive,probation,terminated,resigned,retired'],
            'shift_id'         => ['required', 'exists:shifts,id'],
            // Plain 3-digit employee number, no branch prefix — the branch
            // itself is identified separately, from the biometric upload's
            // own Person ID prefix matched against Branch.code. Uniqueness
            // is scoped per branch (not global), mirroring employee_code
            // above, since two different branches may legitimately number
            // an employee "002".
            'biometric_number' => [
                'required', 'digits:3',
                Rule::unique('employees', 'biometric_number')
                    ->where(fn ($query) => $query->where('branch_id', BranchScope::currentBranchId() ?? request()->input('branch_id')))
                    ->whereNull('deleted_at')
                    ->ignore($excludeId),
            ],
            // Employee Information — Basic Salary auto-matches a Salary
            // Slab (by range, within this employee's own branch) and the
            // matched slab's earnings are offered back as a Yes/No
            // multi-select; only the "Yes" ones are kept.
            'basic_salary'        => ['required', 'numeric', 'min:0'],
            'salary_earnings'     => ['nullable', 'array'],
            'salary_earnings.*'   => ['integer'],
            'weekly_off_rule_id'  => ['nullable', 'exists:rules,id'],
            'attendance_rule_id'  => ['nullable', 'exists:rules,id'],
            'payroll_rule_id'     => ['nullable', 'exists:rules,id'],
            'reporting_to'     => ['nullable', 'exists:employees,id'],
            'address_line1'    => ['required', 'string', 'max:255'],
            'address_line2'    => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'district'         => ['required', 'string', 'max:100'],
            'state'            => ['required', 'string', Rule::in(config('states', []))],
            'pincode'          => ['required', 'digits:6'],
            'permanent_address_line1' => ['required_unless:same_as_current_address,1', 'nullable', 'string', 'max:255'],
            'permanent_address_line2' => ['nullable', 'string', 'max:255'],
            'permanent_city'          => ['nullable', 'string', 'max:100'],
            'permanent_district'      => ['nullable', 'string', 'max:100'],
            'permanent_state'         => ['nullable', 'string', 'max:100'],
            'permanent_pincode'       => ['nullable', 'digits:6'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'digits:10'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'profile_photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            // Statutory Details — step 5, rendered as Yes/No dropdowns.
            'is_pf_applicable' => ['required', 'in:yes,no'],
            'pf_number'        => ['nullable', 'required_if:is_pf_applicable,yes', 'string', 'max:30'],
            'uan_number'       => ['nullable', 'string', 'max:30'],
            'is_esi_applicable'=> ['required', 'in:yes,no'],
            'esi_number'       => ['nullable', 'required_if:is_esi_applicable,yes', 'string', 'max:30'],
            'is_tds_applicable'=> ['required', 'in:yes,no'],
            'tds_number'       => ['nullable', 'required_if:is_tds_applicable,yes', 'string', 'max:30'],
            'is_earnings_applicable' => ['required', 'in:yes,no'],
            'is_ot_applicable' => ['required', 'in:yes,no'],
            'ot_hourly_rate'   => ['nullable', 'required_if:is_ot_applicable,yes', 'numeric', 'min:0'],
            'contractor_id'    => ['required_if:employee_category,contract_labour', 'nullable', 'exists:contractors,id'],
            'contractor_employee_number' => ['nullable', 'string', 'max:50'],
            'work_order_number'          => ['nullable', 'string', 'max:50'],
            'labour_category'            => ['nullable', 'string', 'max:50'],
            'contractor_rate'            => ['nullable', 'numeric', 'min:0'],
            'contractor_remarks'         => ['nullable', 'string'],
            // Bank Details — step 4, shown unless Employee Category is
            // Contract Labour (contractor's own bank details are out of
            // scope for this employee record).
            'bank_id'              => ['nullable', 'exists:banks,id'],
            'bank_name'            => ['nullable', 'string', 'max:100'],
            'account_holder_name'  => ['nullable', 'string', 'max:100'],
            'account_number'       => ['nullable', 'string', 'max:50'],
            'account_number_confirmation' => ['nullable', 'same:account_number'],
            'ifsc_code'            => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'branch_name'          => ['nullable', 'string', 'max:100'],
            'account_type'         => ['nullable', 'in:savings,current,salary'],
        ];

        return $rules;
    }
}
