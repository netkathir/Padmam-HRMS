<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Department;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $data = $request->validate($this->rules($request->input('department_id')));

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

        $data = $request->validate($this->rules($request->input('department_id'), $designation->id));

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
     * Designation.name/code are scoped by department_id (which is itself
     * per-branch, see DepartmentController), rather than a separate
     * branch_id column — two different departments (in the same or
     * different branches) may legitimately share a designation name/code,
     * matching how Department scoping already works. A NULL department_id
     * (global designation) is scoped against other NULL-department rows.
     */
    private function rules(?string $departmentId, ?int $designationId = null): array
    {
        $departmentId = $departmentId !== null && $departmentId !== '' ? (int) $departmentId : null;

        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->where('department_id', $departmentId)->whereNull('deleted_at')->ignore($designationId)],
            'code'          => ['nullable', 'string', 'max:20', Rule::unique('designations', 'code')->where('department_id', $departmentId)->whereNull('deleted_at')->ignore($designationId)],
            'grade'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['boolean'],
        ];
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
