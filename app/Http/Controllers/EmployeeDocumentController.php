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

        return view('employee-document.index', compact('employees', 'notUploaded'));
    }

    /** Read-only Employee Document detail — the uploaded documents list, distinct from employees.documents' own view+upload page. */
    public function show(Employee $employee)
    {
        BranchScope::assertBranchAccess($employee->branch_id);
        $documents = $employee->documents()->latest()->get();

        return view('employee-document.show', compact('employee', 'documents'));
    }

    /**
     * Add flow: Add → dedicated search screen (this page) → pick an
     * employee from the results → employees.documents upload page. Only
     * employees with no documents yet are eligible to appear here, same set
     * the index's "Not Uploaded" filter shows.
     */
    public function create(Request $request)
    {
        $employees = collect();

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $employees = BranchScope::scopeQuery(Employee::query())
                ->doesntHave('documents')
                ->where(fn($q) => $q->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('employee_code', 'like', $s))
                ->orderBy('first_name')
                ->get();
        }

        return view('employee-document.create', compact('employees'));
    }
}
