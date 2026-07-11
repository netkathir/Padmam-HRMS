<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Department;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        // A Designation with no department (department_id null) is global;
        // one tied to a department is only visible if that department
        // belongs to the effective branch.
        $branchId = BranchScope::currentBranchId();
        $query = Designation::with('department')
            ->when($branchId !== null, fn($q) => $q->where(
                fn($q2) => $q2->whereNull('department_id')
                    ->orWhereHas('department', fn($d) => $d->where('branch_id', $branchId))
            ))
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $designations = $query->paginate(20)->withQueryString();
        $departments  = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        return view('masters.designations.index', compact('designations', 'departments'));
    }

    public function create()
    {
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        return view('masters.designations.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['boolean'],
        ]);

        $this->assertDepartmentInScope($data['department_id'] ?? null);

        Designation::create($data);

        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation created successfully.');
    }

    public function edit(Designation $designation)
    {
        $this->assertDepartmentInScope($designation->department_id);
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        return view('masters.designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, Designation $designation)
    {
        $this->assertDepartmentInScope($designation->department_id);

        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['boolean'],
        ]);

        $this->assertDepartmentInScope($data['department_id'] ?? null);

        $designation->update($data);

        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation)
    {
        $this->assertDepartmentInScope($designation->department_id);

        if ($designation->employees()->exists()) {
            return back()->with('error', 'Cannot delete designation with associated employees.');
        }
        $designation->delete();
        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation deleted successfully.');
    }

    /**
     * A NULL department_id (global designation) is always in scope. A set
     * department_id must belong to the effective branch.
     */
    private function assertDepartmentInScope(?int $departmentId): void
    {
        if ($departmentId === null) {
            return;
        }

        $department = Department::find($departmentId);
        BranchScope::assertBranchAccess($department?->branch_id);
    }
}
