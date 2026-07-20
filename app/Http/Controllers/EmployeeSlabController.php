<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SalarySlab;
use App\Support\BranchScope;
use Illuminate\Http\Request;

/**
 * People Module Update — Employee Slab: Employment Information, Bank
 * Information, and Designation & Salary, moved out of the Create Employee
 * wizard into their own single-step module. Deliberately thin — every save
 * action posts to EmployeeController's existing employees.update /
 * employees.bank-details.store / employees.salary.store routes (the exact
 * same validation/persistence those already use), so there is no duplicated
 * business logic here; this controller only owns the list, the "not created"
 * filter, and the employee-picker used to reach the single-step form.
 */
class EmployeeSlabController extends Controller
{
    /** An employee counts as "slab created" once they have a current salary structure (the last of the 3 sections to be saved). */
    private function hasSlabQuery($query, bool $has)
    {
        return $has
            ? $query->whereHas('currentSalary')
            : $query->whereDoesntHave('currentSalary');
    }

    public function index(Request $request)
    {
        $filter = $request->input('filter');
        $notCreated = $filter === 'not_created';

        $query = Employee::with(['branch', 'department', 'designation', 'currentSalary'])
            ->orderBy('first_name');
        $query = BranchScope::scopeQuery($query);
        $query = $this->hasSlabQuery($query, ! $notCreated);

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('employee_code', 'like', $s));
        }

        $employees = $query->paginate(20)->withQueryString();

        // The Add button's employee picker only ever offers employees who
        // don't yet have a slab — same set the "not created" filter shows.
        $pickerEmployees = BranchScope::scopeQuery(Employee::query())
            ->whereDoesntHave('currentSalary')
            ->orderBy('first_name')
            ->get();

        return view('employee-slab.index', compact('employees', 'notCreated', 'pickerEmployees'));
    }

    /** Employee picker landed here with ?employee=ID — redirect straight into that employee's single-step form. */
    public function create(Request $request)
    {
        $employeeId = $request->integer('employee');
        $employee = $employeeId ? Employee::findOrFail($employeeId) : null;

        if (! $employee) {
            return redirect()->route('employee-slab.index');
        }

        return redirect()->route('employee-slab.edit', $employee);
    }

    /** Read-only Employee Slab detail — Employment/Bank/Designation & Salary, distinct from employees.show's own personal-info profile. */
    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['bankDetails.bank', 'currentSalary.components', 'department', 'designation', 'designationContractor', 'shift', 'reportingTo', 'contractor']);

        $canViewFullBankDetails = \App\Support\SensitiveDataAccess::canView('employees');

        return view('employee-slab.show', compact('employee', 'canViewFullBankDetails'));
    }

    public function edit(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $employee->load(['bankDetails.bank', 'currentSalary.components']);

        $salarySlabs = SalarySlab::where('is_active', true)->get();
        $salaryHistory = $employee->salaryHistory()->with('slab')->get();

        // Always read from the Salary Slab currently assigned to the
        // employee's salary record — never a historical snapshot's own
        // stored figures — so the page shows the slab's ACTUAL current
        // configuration even if it was edited after this employee's salary
        // was last saved.
        $currentSalaryBreakdown = ($employee->currentSalary && $employee->currentSalary->slab)
            ? app(EmployeeController::class)->buildSalarySlabBreakdown($employee->currentSalary->slab)
            : null;

        $formData = app(EmployeeController::class)->formData($employee);

        return view('employee-slab.edit', array_merge(
            compact('employee', 'salarySlabs', 'salaryHistory', 'currentSalaryBreakdown'),
            $formData
        ));
    }
}
