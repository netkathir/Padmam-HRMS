<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(
            Employee::with(['branch', 'department', 'designation', 'currentSalary'])
        )->orderBy('employee_code');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('employee_code', 'like', $s)
                    ->orWhere('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('display_name', 'like', $s);
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->paginate(20)->withQueryString();
        $departments = BranchScope::scopeQuery(\App\Models\Department::query())->orderBy('name')->get();

        return view('reports.salary-structure.index', compact('employees', 'departments'));
    }

    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);

        $employee->load(['branch', 'department', 'designation', 'currentSalary.components', 'currentSalary.slab']);

        $latestPayroll = $employee->payrollRecords()
            ->orderByDesc('year')->orderByDesc('month')
            ->first();

        return view('reports.salary-structure.show', compact('employee', 'latestPayroll'));
    }
}
