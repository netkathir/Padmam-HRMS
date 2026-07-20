<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\BranchScope;
use Illuminate\Http\Request;

/**
 * People Module Update — Employee Document: lists every employee with an
 * Action to add/view documents, plus a "not uploaded" filter (employees with
 * zero documents) whose rows only offer Add. Deliberately thin — uploading
 * itself still goes through EmployeeController's existing
 * employees.documents.upload route (documents() there also still serves the
 * per-employee documents list/upload page this links to).
 */
class EmployeeDocumentController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter');
        $notUploaded = $filter === 'not_uploaded';

        $query = Employee::with(['branch', 'department'])->withCount('documents')->orderBy('first_name');
        $query = BranchScope::scopeQuery($query);
        $query = $notUploaded ? $query->doesntHave('documents') : $query->has('documents');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('employee_code', 'like', $s));
        }

        $employees = $query->paginate(20)->withQueryString();

        // The Add button's employee picker only ever offers employees with
        // no documents yet — same set the "not uploaded" filter shows.
        $pickerEmployees = BranchScope::scopeQuery(Employee::query())
            ->doesntHave('documents')
            ->orderBy('first_name')
            ->get();

        return view('employee-document.index', compact('employees', 'notUploaded', 'pickerEmployees'));
    }

    /** Read-only Employee Document detail — the uploaded documents list, distinct from employees.documents' own view+upload page. */
    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $documents = $employee->documents()->latest()->get();

        return view('employee-document.show', compact('employee', 'documents'));
    }

    /** Employee picker landed here with ?employee=ID — redirect straight into that employee's document upload page. */
    public function create(Request $request)
    {
        $employeeId = $request->integer('employee');
        $employee = $employeeId ? Employee::findOrFail($employeeId) : null;

        if (! $employee) {
            return redirect()->route('employee-document.index');
        }

        return redirect()->route('employees.documents', $employee);
    }
}
