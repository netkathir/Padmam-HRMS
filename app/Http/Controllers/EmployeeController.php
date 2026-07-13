<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\Contractor;
use App\Models\Shift;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['created_by'] = auth()->id();
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id']);
        $this->assertDepartmentBelongsToBranch($data['department_id'], $data['branch_id']);
        $this->assertDesignationBelongsToBranch($data['designation_id'] ?? null, $data['branch_id']);
        $this->assertDepartmentIsActive($data['department_id'] ?? null);

        $employee = DB::transaction(function () use ($data, $request) {
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

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['branch', 'department', 'designation', 'employeeType', 'contractor',
            'shift', 'reportingTo', 'user', 'bankDetails', 'currentSalary', 'exitRecord']);
        return view('employees.show', compact('employee'));
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
        // A branch-scoped actor can never move an employee to another branch,
        // even via a crafted request — branch_id is always re-forced here,
        // exactly as on create.
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        $this->assertDepartmentBelongsToBranch($data['department_id'], $data['branch_id']);
        $this->assertDesignationBelongsToBranch($data['designation_id'] ?? null, $data['branch_id']);
        $this->assertDepartmentIsActive($data['department_id'], $employee);
        $employee->update($data);
        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->update(['status' => 'terminated']);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee removed.');
    }

    public function documents(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $documents = $employee->documents()->latest()->get();
        return view('employees.documents', compact('employee', 'documents'));
    }

    public function uploadDocument(Request $request, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $request->validate([
            'document_type'   => ['required', 'string'],
            'document_number' => ['nullable', 'string'],
            'file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('file')->store('employee-documents/' . $employee->id, 'public');
        $employee->documents()->create([
            'document_type'   => $request->document_type,
            'document_number' => $request->document_number,
            'file_path'       => $path,
        ]);

        return back()->with('success', 'Document uploaded.');
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

        $data = $request->validate([
            'slab_id'          => ['required', 'exists:salary_slabs,id'],
            'ctc'              => ['required', 'numeric', 'min:0'],
            'basic'            => ['required', 'numeric', 'min:0'],
            'hra'              => ['required', 'numeric', 'min:0'],
            'other_allowances' => ['required', 'numeric', 'min:0'],
            'effective_from'   => ['required', 'date'],
            'pf_applicable'    => ['boolean'],
            'esi_applicable'   => ['boolean'],
        ]);
        $data['employee_id'] = $employee->id;
        $data['created_by']  = auth()->id();

        $employee->salaryHistory()->update(['is_current' => false]);
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
            throw \Illuminate\Validation\ValidationException::withMessages([
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
            throw \Illuminate\Validation\ValidationException::withMessages([
                'designation_id' => 'The selected designation does not belong to the selected branch.',
            ]);
        }
    }

    private function formData(?Employee $employee = null): array
    {
        $currentBranchId = BranchScope::currentBranchId();

        // A branch is already in effect (branch-scoped actor, or a Super
        // Admin who always has one selected) — lock the Branch field to it
        // instead of offering every branch, since store()/update() force it
        // to this same branch via BranchScope::stampBranchId() regardless of
        // what's submitted. Only genuinely unscoped legacy accounts (null)
        // get a free pick from every active branch.
        $branches = $currentBranchId
            ? Branch::where('id', $currentBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        return [
            'branches'      => $branches,
            'lockedBranchId'=> $currentBranchId,
            // Inactive departments are excluded from new-employee assignment
            // (FSD 7.1); an employee's own already-assigned department is kept
            // in the list on the edit form even if it has since gone inactive,
            // so the field doesn't silently render blank.
            'departments'   => BranchScope::scopeQuery(Department::query())
                ->where(fn($q) => $q->where('is_active', true)->when($employee?->department_id, fn($q2) => $q2->orWhere('id', $employee->department_id)))
                ->orderBy('name')->get(),
            'designations'  => $this->scopedDesignations($currentBranchId),
            'employeeTypes' => EmployeeType::where('is_active', true)->get(),
            'contractors'   => BranchScope::scopeQueryIncludingGlobal(Contractor::where('is_active', true))->orderBy('name')->get(),
            'shifts'        => Shift::where('is_active', true)->get(),
            'managers'      => BranchScope::scopeQuery(Employee::active())->orderBy('first_name')->get(),
            'roles'         => \App\Models\Role::orderBy('name')->get(),
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
        return [
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'employee_code'    => ['required', 'string', 'max:20', 'unique:employees,employee_code,' . $excludeId],
            'branch_id'        => ['required', 'exists:branches,id'],
            'department_id'    => ['required', 'exists:departments,id'],
            'designation_id'   => ['required', 'exists:designations,id'],
            'employee_type_id' => ['required', 'exists:employee_types,id'],
            'date_of_joining'  => ['required', 'date'],
            'date_of_birth'    => ['required', 'date'],
            'gender'           => ['required', 'in:male,female,other'],
            'official_email'   => ['required', 'email', 'max:255', 'unique:employees,official_email,' . $excludeId],
            'phone'            => ['required', 'string', 'max:20'],
            'status'           => ['required', 'in:active,inactive,probation,terminated'],
            'shift_id'         => ['nullable', 'exists:shifts,id'],
            'reporting_to'     => ['nullable', 'exists:employees,id'],
            'address_line1'    => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'pincode'          => ['nullable', 'string', 'max:10'],
            'aadhaar_number'   => ['nullable', 'string', 'max:20'],
            'pan_number'       => ['nullable', 'string', 'max:20'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable'=> ['boolean'],
            'is_tds_applicable'=> ['boolean'],
        ];
    }
}
