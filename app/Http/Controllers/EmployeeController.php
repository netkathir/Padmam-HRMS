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
        $query = Employee::with(['branch', 'department', 'designation', 'employeeType'])
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
        return view('employees.create', $this->formData());
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
     * branch-wise sequence (one higher than the latest code already used in
     * that branch) — Employee Code is no longer manually entered, on this
     * form or as a bare fallback.
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
     * provided), or — when no rule is configured — the branch-wise default
     * sequence. Callable again on its own (with employee_code reset to
     * null) to regenerate a fresh candidate if the first one collides at
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

        return ($data['employee_code'] ?? null) ?: $this->generateDefaultBranchEmployeeCode($data['branch_id'] ?? null);
    }

    /**
     * Default Employee Code generator used when no Employee Number Rule is
     * configured — each branch keeps its own independent sequence, prefixed
     * with the first two characters of that branch's own Branch Code (e.g.
     * Branch Code "CH001" -> "CH0001", "CH0002"...; a different branch with
     * Branch Code "MD005" independently reaches "MD0001", "MD0002"...).
     * Only codes already carrying that branch's prefix are considered "the
     * last one" — older codes from before this prefix existed (or a
     * differently-prefixed branch) are ignored, so the branch's sequence
     * restarts cleanly at 0001 the first time this runs for it. A row lock
     * on the branch's latest matching employee serializes concurrent
     * creations within the same branch; the retry loop is a defensive
     * fallback against the rare duplicate-key race the lock doesn't cover —
     * the composite unique index on (branch_id, employee_code) is the
     * actual guarantee.
     */
    private function generateDefaultBranchEmployeeCode(?int $branchId): string
    {
        $prefix = $this->branchCodePrefix($branchId);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($branchId, $prefix) {
                    $lastCode = Employee::where('branch_id', $branchId)
                        ->where('employee_code', 'like', $prefix . '%')
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

    /** First two characters of the branch's own Branch Code, uppercased. */
    private function branchCodePrefix(?int $branchId): string
    {
        $branchCode = $branchId ? Branch::find($branchId)?->code : null;

        return $branchCode ? strtoupper(substr($branchCode, 0, 2)) : 'EMP';
    }

    /**
     * Creates the employee (and its login user, if requested) in a
     * transaction. The row lock in generateDefaultBranchEmployeeCode()/the
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
        $data = $request->validate($this->rules());
        $data['created_by'] = auth()->id();
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id']);

        $this->assertContractorForLabour($data);
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

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['branch', 'department', 'designation', 'employeeType', 'contractor',
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

    public function edit(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        return view('employees.edit', array_merge(compact('employee'), $this->formData($employee)));
    }

    public function update(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $data = $request->validate($this->rules($employee->id));
        if (empty($data['employee_code'])) {
            // employee_code is only nullable in rules() to support
            // auto-generation on create; on update an empty submission
            // must never blank out an existing employee's code.
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

    public function uploadDocument(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $request->validate([
            'document_type'   => ['required', 'in:aadhaar,pan,passport,offer_letter,resume,relieving_letter,experience_letter,education_certificate,photo,photo_id,bank_proof,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'expiry_date'     => ['nullable', 'date'],
            'file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('employee-documents/' . $employee->id, 'public');

        // Fixes a previously-broken insert: document_name/file_size/file_type/
        // uploaded_by are NOT NULL columns the controller never populated,
        // and document_number/expiry_date/is_verified now exist on the table
        // (2026_07_17_000004 migration) matching what this always tried to save.
        $employee->documents()->create([
            'document_type'   => $request->document_type,
            'document_name'   => $file->getClientOriginalName(),
            'document_number' => $request->document_number,
            'file_path'       => $path,
            'file_size'       => $file->getSize(),
            'file_type'       => $file->getClientMimeType(),
            'expiry_date'     => $request->expiry_date,
            'uploaded_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded.');
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
            'payment_mode'         => ['required', 'in:bank_transfer,cash,cheque'],
            'bank_id'              => ['nullable', 'exists:banks,id'],
            'bank_name'            => ['required_if:payment_mode,bank_transfer', 'nullable', 'string', 'max:100'],
            'account_holder_name'  => ['required_if:payment_mode,bank_transfer', 'nullable', 'string', 'max:100'],
            'account_number'       => ['required_if:payment_mode,bank_transfer', 'nullable', 'string', 'max:50'],
            'account_number_confirmation' => ['required_if:payment_mode,bank_transfer', 'nullable', 'same:account_number'],
            'ifsc_code'            => ['required_if:payment_mode,bank_transfer', 'nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'branch_name'          => ['nullable', 'string', 'max:100'],
            'account_type'         => ['nullable', 'in:savings,current,salary'],
            'is_primary'           => ['boolean'],
        ];
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

        return back()->with('success', 'Bank details added.');
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

        return back()->with('success', 'Bank details updated.');
    }

    public function destroyBankDetail(Employee $employee, EmployeeBankDetail $bankDetail)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        abort_if($bankDetail->employee_id !== $employee->id, 404);

        $bankDetail->delete();

        return back()->with('success', 'Bank details removed.');
    }

    public function salary(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'employees', 'view_sensitive')) {
            abort(403, 'You do not have the "View Sensitive Data" permission for Employees in Branch Administration.');
        }

        $salary  = $employee->currentSalary;
        $history = $employee->salaryHistory()->with('slab')->get();
        $slabs   = \App\Models\SalarySlab::where('is_active', true)->get();
        return view('employees.salary', compact('employee', 'salary', 'history', 'slabs'));
    }

    public function storeSalary(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'employees', 'view_sensitive')) {
            abort(403, 'You do not have the "View Sensitive Data" permission for Employees in Branch Administration.');
        }

        // Fixes a pre-existing bug: these field names didn't match
        // EmployeeSalaryStructure's real fillable columns (was validating
        // slab_id/basic/other_allowances — the model has salary_slab_id/
        // basic_salary/da/ta/medical_allowance/special_allowance instead),
        // so every salary structure saved through this form had those
        // fields silently stuck at their defaults, directly corrupting
        // payroll math that reads basic_salary/da/ta straight off this row.
        $data = $request->validate([
            'salary_slab_id'    => ['nullable', 'exists:salary_slabs,id'],
            'ctc'               => ['required', 'numeric', 'min:0'],
            'basic_salary'      => ['required', 'numeric', 'min:0'],
            'hra'               => ['nullable', 'numeric', 'min:0'],
            'da'                => ['nullable', 'numeric', 'min:0'],
            'ta'                => ['nullable', 'numeric', 'min:0'],
            'medical_allowance' => ['nullable', 'numeric', 'min:0'],
            'special_allowance' => ['nullable', 'numeric', 'min:0'],
            'effective_from'    => ['required', 'date'],
            'pf_applicable'     => ['boolean'],
            'esi_applicable'    => ['boolean'],
        ]);

        // pf_applicable/esi_applicable describe the EMPLOYEE's own
        // applicability flags (Employee.is_pf_applicable/is_esi_applicable
        // from Module 6), not a column on EmployeeSalaryStructure — updating
        // the employee directly here, separate from the salary row itself.
        $employee->update([
            'is_pf_applicable'  => $request->boolean('pf_applicable'),
            'is_esi_applicable' => $request->boolean('esi_applicable'),
        ]);
        unset($data['pf_applicable'], $data['esi_applicable']);

        $data['employee_id']  = $employee->id;
        $data['created_by']   = auth()->id();
        $data['gross_salary'] = $data['basic_salary']
            + (float) ($data['hra'] ?? 0) + (float) ($data['da'] ?? 0) + (float) ($data['ta'] ?? 0)
            + (float) ($data['medical_allowance'] ?? 0) + (float) ($data['special_allowance'] ?? 0);

        $employee->salaryHistory()->update(['is_current' => false, 'effective_to' => now()->toDateString()]);
        $employee->salaryHistory()->create(array_merge($data, ['is_current' => true]));

        return back()->with('success', 'Salary structure saved.');
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
                'designation_id' => 'The selected designation does not belong to the selected branch.',
            ]);
        }
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
            'designations'  => $this->scopedDesignations($currentBranchId),
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
     * Designation dropdown scoped the same way as Masters\DesignationController's
     * own listing — Designation has no branch_id of its own, it's reached
     * transitively via department_id -> departments.branch_id. A NULL
     * department_id designation is kept regardless of branch (not yet
     * assigned to a department, so it can't be excluded by branch).
     */
    private function scopedDesignations(?int $branchId)
    {
        return Designation::query()
            ->when($branchId !== null, fn($q) => $q->where(
                fn($q2) => $q2->whereNull('department_id')
                    ->orWhereHas('department', fn($d) => $d->where('branch_id', $branchId))
            ))
            ->orderBy('name')
            ->get();
    }

    private function rules(int $excludeId = 0): array
    {
        $minAge = (int) Setting::get('employee', 'min_working_age', 18);
        $nameRegex = 'regex:/^[a-zA-Z .\'-]+$/';

        return [
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
            'designation_id'   => ['required', 'exists:designations,id'],
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
            'aadhaar_number'   => ['nullable', 'digits:12', Rule::unique('employees', 'aadhaar_number')->ignore($excludeId)],
            'pan_number'       => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', Rule::unique('employees', 'pan_number')->ignore($excludeId)],
            'uan_number'       => ['nullable', 'digits:12', 'required_if:is_pf_applicable,1', Rule::unique('employees', 'uan_number')->ignore($excludeId)],
            'pf_number'        => ['nullable', 'string', 'max:30', 'required_if:is_pf_applicable,1'],
            'esi_number'       => ['nullable', 'digits:10', 'required_if:is_esi_applicable,1', Rule::unique('employees', 'esi_number')->ignore($excludeId)],
            'passport_number'  => ['nullable', 'string', 'max:20'],
            'passport_expiry'  => ['nullable', 'date'],
            'profile_photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable'=> ['boolean'],
            'is_tds_applicable'=> ['boolean'],
            'contractor_id'    => ['required_if:employee_category,contract_labour', 'nullable', 'exists:contractors,id'],
            'contractor_employee_number' => ['nullable', 'string', 'max:50'],
            'work_order_number'          => ['nullable', 'string', 'max:50'],
            'labour_category'            => ['nullable', 'string', 'max:50'],
            'contractor_rate'            => ['nullable', 'numeric', 'min:0'],
            'contractor_remarks'         => ['nullable', 'string'],
        ];
    }
}
