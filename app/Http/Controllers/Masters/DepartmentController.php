<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with('branch')->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $departments = $query->paginate(20)->withQueryString();
        $branches    = Branch::orderBy('name')->get();
        return view('masters.departments.index', compact('departments', 'branches'));
    }

    public function create()
    {
        $branches = Branch::active()->orderBy('name')->get();
        return view('masters.departments.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        Department::create($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        $branches = Branch::active()->orderBy('name')->get();
        return view('masters.departments.edit', compact('department', 'branches'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $department->update($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->designations()->exists()) {
            return back()->with('error', 'Cannot delete department with associated designations.');
        }
        $department->delete();
        return redirect()->route('masters.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
