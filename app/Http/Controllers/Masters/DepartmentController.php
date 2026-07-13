<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Branch;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Department::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped actor
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $departments = $query->paginate(20)->withQueryString();
        $branches    = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.departments.index', compact('departments', 'branches'));
    }

    public function create()
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::active()->orderBy('name')->get() : Branch::where('id', BranchScope::currentBranchId())->get();
        $lockedBranchId = $isSuperAdmin ? null : BranchScope::currentBranchId();
        return view('masters.departments.create', compact('branches', 'lockedBranchId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);

        Department::create($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::active()->orderBy('name')->get() : Branch::where('id', $department->branch_id)->get();
        $lockedBranchId = $isSuperAdmin ? null : $department->branch_id;
        return view('masters.departments.edit', compact('department', 'branches', 'lockedBranchId'));
    }

    public function update(Request $request, Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);

        $department->update($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);

        if ($department->designations()->exists()) {
            return back()->with('error', 'Cannot delete department with associated designations.');
        }
        $department->delete();
        return redirect()->route('masters.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
