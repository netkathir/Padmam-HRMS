<?php
/**
 * File: app/Http/Controllers/Masters/ContractorController.php
 * Purpose: CRUD and management for Contractors — labour assignment, contractor-wise attendance and payroll views.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PayrollRecord;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class ContractorController extends Controller
{
    public function index(Request $request)
    {
        $query = Contractor::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('code', 'like', $s)
                ->orWhere('contact_person', 'like', $s));
        }

        $contractors = $query->paginate(20)->withQueryString();
        return view('masters.contractors.index', compact('contractors'));
    }

    public function create()
    {
        return view('masters.contractors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'company_name'   => ['nullable', 'string', 'max:150'],
            'code'           => ['required', 'string', 'max:20', 'unique:contractors,code'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:150'],
            'address'        => ['nullable', 'string'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'license_expiry' => ['nullable', 'date'],
            'is_active'      => ['boolean'],
        ]);

        Contractor::create($data);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor created successfully.');
    }

    public function edit(Contractor $contractor)
    {
        return view('masters.contractors.edit', compact('contractor'));
    }

    public function update(Request $request, Contractor $contractor)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'company_name'   => ['nullable', 'string', 'max:150'],
            'code'           => ['required', 'string', 'max:20', 'unique:contractors,code,' . $contractor->id],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:150'],
            'address'        => ['nullable', 'string'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'license_expiry' => ['nullable', 'date'],
            'is_active'      => ['boolean'],
        ]);

        $contractor->update($data);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor updated successfully.');
    }

    public function destroy(Contractor $contractor)
    {
        if ($contractor->employees()->exists()) {
            return back()->with('error', 'Cannot delete contractor with associated employees.');
        }
        $contractor->delete();
        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor deleted successfully.');
    }

    // ── Contract Labour Assignment (standalone page) ────────────────────

    /**
     * Standalone contractor selector page — mirrors contract-attendance UI pattern.
     */
    public function contractLabourIndex(Request $request)
    {
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $contractor          = null;
        $employees           = collect();
        $unassignedEmployees = collect();

        if ($request->filled('contractor_id')) {
            $contractor = Contractor::findOrFail($request->contractor_id);

            $employees = BranchScope::scopeQuery($contractor->employees())
                ->with(['department', 'designation'])
                ->orderBy('first_name')
                ->paginate(20)
                ->withQueryString();

            $unassignedEmployees = BranchScope::scopeQuery(
                Employee::active()->whereNull('contractor_id')
            )
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'employee_code', 'branch_id']);
        }

        return view('contract-labour.index', compact('contractors', 'contractor', 'employees', 'unassignedEmployees'));
    }

    // ── Contractor Labour Management ─────────────────────────────────────

    /**
     * Show all employees (labour) assigned to a specific contractor.
     */
    public function labour(Contractor $contractor)
    {
        $employees = BranchScope::scopeQuery($contractor->employees())
            ->with(['department', 'designation', 'shift'])
            ->orderBy('first_name')
            ->paginate(20);

        $unassignedEmployees = BranchScope::scopeQuery(
            Employee::active()->whereNull('contractor_id')
        )
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code', 'branch_id']);

        return view('masters.contractors.labour.index', compact('contractor', 'employees', 'unassignedEmployees'));
    }

    /**
     * Assign an employee to this contractor.
     */
    public function assignLabour(Request $request, Contractor $contractor)
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        BranchScope::assertBranchAccess($employee->branch_id);

        if ($employee->contractor_id) {
            return back()->with('error', 'Employee is already assigned to a contractor.');
        }

        $employee->update(['contractor_id' => $contractor->id]);

        return back()->with('success', 'Employee assigned to contractor successfully.');
    }

    /**
     * Remove an employee from this contractor.
     */
    public function removeLabour(Contractor $contractor, Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        if ($employee->contractor_id !== $contractor->id) {
            return back()->with('error', 'Employee is not assigned to this contractor.');
        }

        $employee->update(['contractor_id' => null]);

        return back()->with('success', 'Employee removed from contractor successfully.');
    }

    // ── Contractor-wise Attendance ───────────────────────────────────────

    /**
     * View attendance filtered by contractor.
     */
    public function attendance(Request $request, Contractor $contractor)
    {
        $date = $request->input('date', now()->toDateString());

        $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');

        $query = Attendance::with(['employee.department'])
            ->whereIn('employee_id', $employeeIds)
            ->where('date', $date)
            ->orderBy('in_time');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance  = $query->paginate(25)->withQueryString();
        $summary     = Attendance::whereIn('employee_id', $employeeIds)
            ->where('date', $date)
            ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('masters.contractors.labour.attendance', compact('contractor', 'attendance', 'summary', 'date'));
    }

    // ── Contractor-wise Payroll ──────────────────────────────────────────

    /**
     * View payroll records filtered by contractor.
     */
    public function payroll(Request $request, Contractor $contractor)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');

        $records = PayrollRecord::with(['employee.department'])
            ->whereIn('employee_id', $employeeIds)
            ->where('month', $month)->where('year', $year)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();

        $summary = PayrollRecord::whereIn('employee_id', $employeeIds)
            ->where('month', $month)->where('year', $year)
            ->selectRaw('COUNT(*) as count, SUM(gross_earnings) as gross, SUM(net_salary) as net, SUM(total_deductions) as deductions')
            ->first();

        return view('masters.contractors.labour.payroll', compact('contractor', 'records', 'summary', 'month', 'year'));
    }
}
