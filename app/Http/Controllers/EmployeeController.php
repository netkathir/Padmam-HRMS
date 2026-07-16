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
        $query = Employee::with(['branch', 'department', 'designation', 'employeeType', 'designationContractor'])
            ->orderBy('first_name');

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
        $activeTab = 1;
        // Tabs 8-10 (Bank/Salary/Documents) are directly fillable on Create
        // too, so this needs the same reference data edit() already passes
        // for those tabs.
        $salarySlabs = \App\Models\SalarySlab::where('is_active', true)->get();

        return view('employees.create', $this->formData() + compact(
            'activeTab', 'salarySlabs'
        ));
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

        $prefix = $this->employeeTypePrefix($request->integer('employee_type_id') ?: null);
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
     * Module 4 FSD 8.3 — resolve and apply the Employee Number Rule, if any,
     * for this new hire's classification/branch. If no Employee Number Rule
     * is configured in the Rule Engine, this falls back to a simple
     * sequence prefixed by the selected Employee Type (one higher than the
     * latest code already using that prefix) — Employee Code is no longer
     * manually entered, on this form or as a bare fallback.
     */
    private function applyEmployeeNumberRule(array $data): array
    {
        $category = $data['employee_category'];
        $primaryType = $category === 'staff' ? 'staff' : 'labour';
        $labourType = self::LABOUR_TYPE_MAP[$category];
        $data['primary_employee_type'] = $primaryType;
        $data['labour_type'] = $labourType;

        $data['employee_code'] = $this->resolveEmployeeCode($data, $primaryType, $labourType);

        return $data;
    }

    /**
     * Decides the Employee Code to use: the applicable Employee Number
     * Rule's generated value (unless manual override is both permitted and
     * provided), or — when no rule is configured — the Employee-Type-prefixed
     * default sequence. Callable again on its own (with employee_code reset
     * to null) to regenerate a fresh candidate if the first one collides at
     * insert time.
     */
    private function resolveEmployeeCode(array $data, string $primaryType, ?string $labourType): ?string
    {
        $generator = app(EmployeeNumberGenerator::class);
        // Contractor-wise numbering: resolved using the contractor now
        // captured directly on this form for Contract Labour (FSD 10.8).
        $rule = $generator->resolveRule($primaryType, $labourType, $data['branch_id'] ?? null, $data['contractor_id'] ?? null);

        if ($rule) {
            $detail = $rule->employeeNumberRule;
            $canManuallyOverride = $detail->allow_manual_override && auth()->user()->can('rule_engine.full');
            if (! $canManuallyOverride || empty($data['employee_code'])) {
                return $generator->generate($rule, $data['branch_id'] ?? null, $data['contractor_id'] ?? null);
            }
            return $data['employee_code'] ?? null;
        }

        return ($data['employee_code'] ?? null) ?: $this->generateDefaultEmployeeCode($data['employee_type_id'] ?? null);
    }

    /**
     * Default Employee Code generator used when no Employee Number Rule is
     * configured — prefixed with the first two letters of the selected
     * Employee Type's name (e.g. "Permanent" -> "PE0001", "PE0002"...;
     * "Contract" -> "CO0001", "CO0002"...), continuing sequentially across
     * every branch that has ever used that same prefix. Only codes already
     * carrying that prefix are considered "the last one" — older codes from
     * before this prefix existed (or with a different prefix) are ignored,
     * so a brand new prefix cleanly starts at 0001. A row lock on the latest
     * matching employee serializes concurrent creations under the same
     * prefix; the retry loop is a defensive fallback against the rare
     * duplicate-key race the lock doesn't cover — the composite unique index
     * on (branch_id, employee_code) is the actual guarantee.
     */
    private function generateDefaultEmployeeCode(?int $employeeTypeId): string
    {
        $prefix = $this->employeeTypePrefix($employeeTypeId);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($prefix) {
                    $lastCode = Employee::where('employee_code', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->value('employee_code');

                    return SequentialCodeGenerator::next($lastCode, $prefix . '0001');
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 5) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique Employee Code after several attempts.');
    }

    /** First two letters of the selected Employee Type's own name, uppercased. */
    private function employeeTypePrefix(?int $employeeTypeId): string
    {
        $name = $employeeTypeId ? EmployeeType::find($employeeTypeId)?->name : null;

        return $name ? strtoupper(substr($name, 0, 2)) : 'EMP';
    }

    /**
     * Creates the employee (and its login user, if requested) in a
     * transaction. The row lock in generateDefaultEmployeeCode()/the
     * Rule Engine's own counter lock make a collision here unlikely but not
     * provably impossible (the lock is released before this insert runs) —
     * if employee_code was auto-generated and this still collides, a fresh
     * code is generated and the insert is retried.
     */
    private function createEmployeeWithRetry(array $data, Request $request, bool $codeWasGenerated, string $primaryType, ?string $labourType): Employee
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $request) {
                    $emp = Employee::create($data);

                    // Create login user if email provided
                    if ($request->filled('official_email') && $request->boolean('create_user')) {
                        User::create([
                            'name'        => $emp->full_name,
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
                if (! $isDuplicate || ! $codeWasGenerated || $attempt === 5) {
                    throw $e;
                }
                $data['employee_code'] = $this->resolveEmployeeCode(
                    array_merge($data, ['employee_code' => null]),
                    $primaryType,
                    $labourType
                );
            }
        }

        throw new \RuntimeException('Unable to create the employee with a unique Employee Code after several attempts.');
    }

    /**
     * Module 6 FSD 10.2 — "Biometric ID shall be unique according to
     * configured scope" (global or per-branch — Setting group `employee`,
     * key `biometric_id_scope`, same Settings-driven pattern as Module 4's
     * Sunday-pay config).
     */
    private function assertBiometricIdUnique(?string $biometricId, ?int $branchId, ?int $excludeId): void
    {
        if (! $biometricId) {
            return;
        }

        $scope = Setting::get('employee', 'biometric_id_scope', 'global');
        $query = Employee::where('biometric_id', $biometricId);
        if ($scope === 'branch' && $branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'biometric_id' => $scope === 'branch'
                    ? 'This Biometric ID is already used by another employee in this branch.'
                    : 'This Biometric ID is already in use.',
            ]);
        }
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
     * FSD 10.3.3 — "Department, designation, and shift shall belong to the
     * selected branch or be globally applicable." Mirrors
     * assertDepartmentBelongsToBranch()/assertDesignationBelongsToBranch() —
     * a Shift with no branch_branches rows is globally applicable (same
     * NULL/empty-means-global convention used throughout this app); one
     * with branch restrictions must include the employee's branch.
     */
    private function assertShiftBelongsToBranch(?int $shiftId, ?int $branchId): void
    {
        if ($shiftId === null || $branchId === null) {
            return;
        }

        $shift = Shift::with('branches')->find($shiftId);
        if ($shift && $shift->branches->isNotEmpty() && ! $shift->branches->contains('id', $branchId)) {
            throw ValidationException::withMessages([
                'shift_id' => 'The selected shift is not applicable to the selected branch.',
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
        if ($data['employee_category'] !== 'contract_labour') {
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

    public function store(Request $request)
    {
        $isDraft = $request->boolean('save_as_draft');
        $data = $request->validate($this->rules(0, $isDraft));
        $data['designation_id'] = $this->resolveDesignationId($data['designation'] ?? null);
        unset($data['designation']);
        $data['created_by'] = auth()->id();
        $data['is_draft'] = $isDraft;
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id']);

        if (! $isDraft) {
            $this->assertContractorForLabour($data);
        }
        $this->assertShiftBelongsToBranch($data['shift_id'] ?? null, $data['branch_id']);
        $this->assertBiometricIdUnique($data['biometric_id'] ?? null, $data['branch_id'], null);
        $data = $this->applySameAsCurrentAddress($request, $data);
        $data = $this->stripUnauthorizedRuleOverrides($data);
        $data = $this->stripUnauthorizedContractorRate($data);

        $submittedCode = $data['employee_code'] ?? null;
        $primaryType = $data['employee_category'] === 'staff' ? 'staff' : 'labour';
        $labourType = self::LABOUR_TYPE_MAP[$data['employee_category']];
        $data = $this->applyEmployeeNumberRule($data);
        $codeWasGenerated = ($data['employee_code'] ?? null) !== $submittedCode;
        unset($data['employee_category']);

        if (empty($data['employee_code'])) {
            return back()->withErrors(['employee_code' => 'Employee Code is required (no numbering rule is configured for this employee category).'])->withInput();
        }

        $this->assertDepartmentBelongsToBranch($data['department_id'], $data['branch_id']);
        $this->assertDesignationBelongsToBranch($data['designation_id'] ?? null, $data['branch_id']);
        $this->assertDepartmentIsActive($data['department_id'] ?? null);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
        }

        if (empty($data['display_name'])) {
            $data['display_name'] = trim(collect([$data['first_name'] ?? null, $data['middle_name'] ?? null, $data['last_name'] ?? null])->filter()->implode(' '));
        }

        $employee = $this->createEmployeeWithRetry($data, $request, $codeWasGenerated, $primaryType, $labourType);

        // FSD — Tabs 8-10 (Bank/Salary/Documents) are directly clickable and
        // fillable on the Create wizard too, even though the employee itself
        // is only created right here; each section is entirely optional
        // (the user may leave any of them for later from Edit) and is
        // processed with the exact same validation/persistence the
        // standalone Tab 8/9/10 routes use.
        // (Payment Mode was removed from this section — gate on whichever
        // bank-identifying field the user actually filled in instead.)
        if ($request->filled('bank_details.bank_id') || $request->filled('bank_details.bank_name') || $request->filled('bank_details.account_number')) {
            $bankData = $request->validate($this->prefixRules('bank_details', $this->bankDetailRules()), [
                'bank_details.ifsc_code.regex' => 'The IFSC code format is invalid (e.g. HDFC0001234).',
                'bank_details.account_number_confirmation.same' => 'Account Number and Confirm Account Number must match.',
            ])['bank_details'];
            unset($bankData['account_number_confirmation']);
            $employee->bankDetails()->create($bankData);
        }
        if ($request->filled('salary.salary_slab_id')) {
            $salaryData = $request->validate($this->prefixRules('salary', $this->salaryRules()))['salary'];
            $this->saveSalaryStructure($employee, $salaryData);
        }
        if ($request->has('documents')) {
            $this->saveDocumentRows($employee, $this->documentRows($request));
        }

        if ($isDraft) {
            return redirect()->route('employees.edit', $employee)
                ->with('success', 'Employee saved as draft. You can continue registration anytime from Edit.');
        }

        // Reaching this via the final tab's "Save Employee" button (clicked
        // before Bank/Salary/Documents could exist yet, since the employee
        // itself didn't) — the employee is now fully created, nothing left
        // to finalize, go straight to the profile.
        if ($request->boolean('finish')) {
            return redirect()->route('employees.show', $employee)
                ->with('success', 'Employee registered successfully.');
        }

        // Tabs 1-7 are complete — continue the wizard into whichever tab the
        // user actually clicked (Tab 7's own "Save & Next", or directly
        // clicking a Tab 8/9/10 header/action before the employee existed
        // yet), now that it has a real id. Defaults to Tab 8 (Bank
        // Information) for any caller that doesn't specify one.
        $nextTab = (int) $request->input('next_tab', 8);
        return redirect()->route('employees.edit', ['employee' => $employee, 'tab' => $nextTab])
            ->with('success', 'Employee details saved. Continue with Bank, Salary, and Documents.');
    }

    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['branch', 'department', 'designation', 'employeeType', 'contractor', 'designationContractor',
            'shift', 'reportingTo', 'user', 'bankDetails.bank', 'currentSalary', 'exitRecord',
            'weeklyOffRuleOverride', 'attendanceRuleOverride', 'payrollRuleOverride']);

        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        // Module 11 (FSD 15.2) — bank/statutory unmasking is gated by the
        // dedicated View Sensitive Data permission, not the coarse `full`
        // access level (previously any user with Edit/Delete access to
        // Employees could also see the unmasked bank account number, which
        // conflated two unrelated permissions).
        $canViewFullBankDetails = \App\Support\SensitiveDataAccess::canView('employees');

        return view('employees.show', compact('employee', 'banks', 'canViewFullBankDetails'));
    }

    public function edit(Employee $employee, Request $request)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        // Tab 1 (Employee Classification) was removed — the wizard now
        // opens on Tab 2 (Personal Information) by default.
        $activeTab = (int) $request->input('tab', 2);
        $employee->load(['documents', 'bankDetails.bank', 'currentSalary.components']);

        // Tabs 8-10 (Bank/Salary/Documents) live in the same wizard shell —
        // the extra data the standalone salary()/documents() actions expose
        // is folded in here too, so those tabs don't need a separate page load.
        $mandatoryDocTypes = json_decode(Setting::get('employee', 'mandatory_document_types', '[]'), true) ?: [];
        $missingMandatoryDocs = array_diff($mandatoryDocTypes, $employee->documents->pluck('document_type')->all());
        $salarySlabs = \App\Models\SalarySlab::where('is_active', true)->get();
        $salaryHistory = $employee->salaryHistory()->with('slab')->get();

        // Always read from the Salary Slab currently assigned to the
        // employee's salary record — never from the historical snapshot's
        // own stored figures — so the tab shows the slab's ACTUAL current
        // configuration even if it was edited after this employee's salary
        // was last saved.
        $currentSalaryBreakdown = ($employee->currentSalary && $employee->currentSalary->slab)
            ? $this->buildSalarySlabBreakdown($employee->currentSalary->slab)
            : null;

        return view('employees.edit', array_merge(
            compact('employee', 'activeTab', 'missingMandatoryDocs', 'salarySlabs', 'salaryHistory', 'currentSalaryBreakdown'),
            $this->formData($employee)
        ));
    }

    public function update(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $isDraft = $request->boolean('save_as_draft');
        $data = $request->validate($this->rules($employee->id, $isDraft));
        $data['designation_id'] = $this->resolveDesignationId($data['designation'] ?? null);
        unset($data['designation']);
        $data['is_draft'] = $isDraft;
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
        if (! $isDraft) {
            $this->assertContractorForLabour($data);
        }
        $this->assertShiftBelongsToBranch($data['shift_id'] ?? null, $data['branch_id']);
        $this->assertBiometricIdUnique($data['biometric_id'] ?? null, $data['branch_id'], $employee->id);
        $this->assertReportingToIsNotSelf($data['reporting_to'] ?? null, $employee->id);
        $data = $this->applySameAsCurrentAddress($request, $data);
        $data = $this->stripUnauthorizedRuleOverrides($data);
        $data = $this->stripUnauthorizedContractorRate($data);

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
        }

        $category = $data['employee_category'];
        $data['primary_employee_type'] = $category === 'staff' ? 'staff' : 'labour';
        $data['labour_type'] = self::LABOUR_TYPE_MAP[$category];
        unset($data['employee_category']);

        $employee->update($data);

        if ($isDraft) {
            return redirect()->route('employees.edit', $employee)
                ->with('success', 'Draft saved. You can continue registration anytime.');
        }

        if ($request->filled('next_tab')) {
            return redirect()->route('employees.edit', ['employee' => $employee, 'tab' => $request->input('next_tab')])
                ->with('success', 'Saved. Continue to the next section.');
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    /**
     * Final tab (Documents) — "Save Employee". If the record is still a
     * draft, this is what promotes it to complete: it validates the
     * employee's already-stored data against the FULL (non-draft) rules —
     * not a new submission, tabs 8-10 don't resubmit tabs 1-7's fields — and
     * only then flips is_draft off. A non-draft employee is already
     * complete, so this is a no-op confirmation for them.
     */
    public function finalize(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        if ($employee->is_draft) {
            $category = $employee->primary_employee_type === 'staff' ? 'staff' : $employee->labour_type;
            $payload = array_merge($employee->toArray(), [
                'employee_category' => $category,
                // rules() now validates the free-text 'designation' field, not
                // the FK 'designation_id' — reconstruct it from whichever
                // Designation record is currently linked, same pattern as
                // employee_category above.
                'designation' => $employee->designation?->name,
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make($payload, $this->rules($employee->id));
            if ($validator->fails()) {
                return redirect()->route('employees.edit', $employee)
                    ->withErrors($validator)
                    ->with('error', 'This employee is still missing required information — please complete the highlighted fields before finishing registration.');
            }

            $employee->update(['is_draft' => false]);
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee registration completed.');
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
     * Employee Master — "Designation & Salary" live preview: returns the
     * selected Salary Slab's full computed breakdown (never trusting a
     * client-supplied figure) so the tab can show it read-only before the
     * employee record is even saved yet.
     */
    public function salarySlabBreakdown(\App\Models\SalarySlab $salarySlab)
    {
        return response()->json($this->buildSalarySlabBreakdown($salarySlab));
    }

    /**
     * Single builder for the read-only salary breakdown shown in the
     * Designation & Salary tab — used both by the live AJAX endpoint above
     * and by the Edit page's initial render, so the values shown when a
     * page first loads are never a step behind a Salary Slab that was
     * edited after an employee's salary was last saved (the Slab is always
     * the single source of truth, never a snapshot recalculation).
     */
    private function buildSalarySlabBreakdown(\App\Models\SalarySlab $salarySlab): array
    {
        $salarySlab->load('components');

        return [
            'basic_salary'    => (float) $salarySlab->basic_salary,
            'gross_salary'    => $salarySlab->gross_salary,
            'total_deductions' => $salarySlab->total_deductions,
            'net_salary'      => $salarySlab->net_salary,
            'ctc'             => $salarySlab->ctc,
            'employer_contributions' => $salarySlab->employer_contributions,
            'pf_employee'  => $salarySlab->pf_employee,
            'pf_employer'  => $salarySlab->pf_employer,
            'esi_employee' => $salarySlab->esi_employee,
            'esi_employer' => $salarySlab->esi_employer,
            'tds'          => $salarySlab->tds,
            'pf_employee_percentage'  => (float) $salarySlab->pf_employee_percentage,
            'pf_employer_percentage'  => (float) $salarySlab->pf_employer_percentage,
            'esi_employee_percentage' => (float) $salarySlab->esi_employee_percentage,
            'esi_employer_percentage' => (float) $salarySlab->esi_employer_percentage,
            'tds_percentage'          => (float) $salarySlab->tds_percentage,
            'earning_components' => $salarySlab->earning_components->map(fn ($c) => [
                'name' => $c->component_name, 'calculation_type' => $c->calculation_type,
                'calculation_base' => $c->calculation_base, 'rate' => (float) $c->rate, 'amount' => (float) $c->calculated_amount,
            ])->values(),
            'deduction_components' => $salarySlab->deduction_components->map(fn ($c) => [
                'name' => $c->component_name, 'calculation_type' => $c->calculation_type,
                'calculation_base' => $c->calculation_base, 'rate' => (float) $c->rate, 'amount' => (float) $c->calculated_amount,
            ])->values(),
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

        // Biometric ID and the PF/ESI/TDS/OT applicability checkboxes now
        // live on this same Designation & Salary tab (moved off the removed
        // Employee Classification/Statutory Information tabs) — this is a
        // standalone route/form on Edit (unlike Create, where they're part
        // of the one combined Tabs-1-10 submission and already handled by
        // the main rules()/store()), so they need their own validation and
        // Employee-record update here.
        $extra = $request->validate($this->employeeTabExtraRules());
        $this->assertBiometricIdUnique($extra['biometric_id'] ?? null, $employee->branch_id, $employee->id);
        $employee->update($extra);

        return $this->redirectAfterTabAction($employee, $request, 'Salary structure saved.');
    }

    /**
     * Biometric ID (required, always manually entered — never
     * auto-generated) and the PF/ESI/TDS/OT applicability checkboxes,
     * relocated onto the Designation & Salary tab. Validated separately
     * from salaryRules() since — on Edit — this tab is its own standalone
     * form/route, distinct from the Tabs-1-7 form these fields used to
     * belong to.
     */
    private function employeeTabExtraRules(): array
    {
        return [
            'biometric_id'      => ['required', 'string', 'max:50'],
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

        $slab = \App\Models\SalarySlab::with('components')->findOrFail($data['salary_slab_id']);

        $structureData = [
            'employee_id'       => $employee->id,
            'salary_slab_id'    => $slab->id,
            'ctc'               => $slab->ctc,
            'basic_salary'      => $slab->basic_salary,
            'hra'               => 0,
            'da'                => 0,
            'ta'                => 0,
            'medical_allowance' => 0,
            'special_allowance' => 0,
            'gross_salary'      => $slab->gross_salary,
            'pf_employee'       => $slab->pf_employee,
            'pf_employer'       => $slab->pf_employer,
            'esi_employee'      => $slab->esi_employee,
            'esi_employer'      => $slab->esi_employer,
            'tds'               => $slab->tds,
            'net_salary'        => $slab->net_salary,
            'effective_from'    => $data['effective_from'],
            'effective_to'      => $data['effective_to'] ?? null,
            'created_by'        => auth()->id(),
        ];

        $employee->salaryHistory()->update(['is_current' => false, 'effective_to' => now()->toDateString()]);
        $structure = $employee->salaryHistory()->create(array_merge($structureData, ['is_current' => true]));

        foreach ($slab->components as $component) {
            $structure->components()->create([
                'component_type'    => $component->component_type,
                'component_id'      => $component->component_id,
                'component_name'    => $component->component_name,
                'calculation_type'  => $component->calculation_type,
                'calculation_base'  => $component->calculation_base,
                'rate'              => $component->rate,
                'calculated_amount' => $component->calculated_amount,
            ]);
        }
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

    private function formData(?Employee $employee = null): array
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
            'contractors'   => Contractor::where('is_active', true)->orderBy('name')->get(),
            'shifts'        => Shift::where('is_active', true)->get(),
            'managers'      => BranchScope::scopeQuery(Employee::active())->orderBy('first_name')->get(),
            'roles'         => \App\Models\Role::orderBy('name')->get(),
            'banks'         => Bank::where('is_active', true)->orderBy('name')->get(),
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
     * Fields that must stay present even on a "Save as Draft" submission —
     * everything else has its required/required_if/required_unless/
     * required_with rules relaxed to nullable. These are exactly the
     * columns that are NOT NULL at the database level with no default, so
     * a draft is always a real, valid row — just not yet complete against
     * the full FSD business validation (addresses, statutory numbers,
     * contract-labour conditionals, etc.), which "Save Employee" enforces.
     */
    private const DRAFT_REQUIRED_FIELDS = [
        'first_name', 'last_name', 'employee_category', 'branch_id', 'department_id',
        'designation', 'employee_type_id', 'date_of_joining', 'gender', 'phone',
    ];

    /** Relaxes every required/required_if/required_unless/required_with rule to nullable, except DRAFT_REQUIRED_FIELDS. */
    private function relaxRulesForDraft(array $rules): array
    {
        foreach ($rules as $field => $fieldRules) {
            if (in_array($field, self::DRAFT_REQUIRED_FIELDS, true)) {
                continue;
            }

            $relaxed = [];
            $hasNullable = false;
            foreach ($fieldRules as $rule) {
                if ($rule === 'required') {
                    continue;
                }
                if (is_string($rule) && (str_starts_with($rule, 'required_if:') || str_starts_with($rule, 'required_unless:') || str_starts_with($rule, 'required_with:'))) {
                    continue;
                }
                if ($rule === 'nullable') {
                    $hasNullable = true;
                }
                $relaxed[] = $rule;
            }
            if (! $hasNullable) {
                array_unshift($relaxed, 'nullable');
            }
            $rules[$field] = $relaxed;
        }

        return $rules;
    }

    private function rules(int $excludeId = 0, bool $isDraft = false): array
    {
        $minAge = (int) Setting::get('employee', 'min_working_age', 18);
        $nameRegex = 'regex:/^[a-zA-Z .\'-]+$/';

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
            'employee_code'    => [
                'nullable', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')
                    ->where(fn ($query) => $query->where('branch_id', BranchScope::currentBranchId() ?? request()->input('branch_id')))
                    ->ignore($excludeId),
            ],
            'biometric_id'     => ['required', 'string', 'max:50'],
            'employee_category' => ['required', 'in:staff,company_labour,contract_labour'],
            'branch_id'        => ['required', 'exists:branches,id'],
            'department_id'    => ['required', 'exists:departments,id'],
            // Designation is no longer picked from the Masters module — the
            // user types it directly; resolveDesignationId() resolves it to
            // (or creates) the matching Designation record right after
            // validation, in both store() and update().
            'designation'      => ['required', 'string', 'max:100'],
            'employee_type_id' => ['required', 'exists:employee_types,id'],
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
            'official_email'   => ['required', 'email', 'max:255', 'unique:employees,official_email,' . $excludeId],
            'phone'            => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'alternate_phone'  => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'status'           => ['required', 'in:active,inactive,probation,terminated,resigned,retired'],
            'shift_id'         => ['nullable', 'exists:shifts,id'],
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
            'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'profile_photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable'=> ['boolean'],
            'is_tds_applicable'=> ['boolean'],
            'is_ot_applicable' => ['boolean'],
            'contractor_id'    => ['required_if:employee_category,contract_labour', 'nullable', 'exists:contractors,id'],
            'contractor_employee_number' => ['nullable', 'string', 'max:50'],
            'work_order_number'          => ['nullable', 'string', 'max:50'],
            'labour_category'            => ['nullable', 'string', 'max:50'],
            'contractor_rate'            => ['nullable', 'numeric', 'min:0'],
            'contractor_remarks'         => ['nullable', 'string'],
        ];

        return $isDraft ? $this->relaxRulesForDraft($rules) : $rules;
    }
}
