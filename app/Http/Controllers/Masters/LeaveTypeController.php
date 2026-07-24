<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LeaveType;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(LeaveType::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $leaveTypes = $query->paginate(20)->withQueryString();
        $branches   = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.leave-types.index', compact('leaveTypes', 'branches'));
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        return view('masters.leave-types.create', compact('currentBranch'));
    }

    private function rules(?int $leaveTypeId = null): array
    {
        $branchId = BranchScope::currentBranchId() ?? request()->input('branch_id');

        return [
            'branch_id'                    => ['required', 'exists:branches,id'],
            // whereNull('deleted_at') is load-bearing on both of these:
            // Rule::unique() has no built-in awareness of soft deletes —
            // without it, a deleted leave type's name/code stays
            // permanently "taken" and can never be reused. Scoped per
            // branch (not global) — two different branches may
            // legitimately register a leave type with the same name/code.
            'name'                         => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->where('branch_id', $branchId)->whereNull('deleted_at')->ignore($leaveTypeId)],
            'code'                         => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')->where('branch_id', $branchId)->whereNull('deleted_at')->ignore($leaveTypeId)],
            'applicable_employee_types'    => ['required', 'array', 'min:1'],
            'applicable_employee_types.*'  => ['in:' . implode(',', array_keys(config('employee_types')))],
            'days_per_year'                => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid'                      => ['required', 'boolean'],
            'is_active'                    => ['required', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);

        LeaveType::create($data);

        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type created successfully.');
    }

    public function edit(LeaveType $leaveType)
    {
        BranchScope::assertBranchAccess($leaveType->branch_id);
        $currentBranch = $leaveType->branch;
        return view('masters.leave-types.edit', compact('leaveType', 'currentBranch'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        BranchScope::assertBranchAccess($leaveType->branch_id);

        $data = $request->validate($this->rules($leaveType->id));

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);

        $leaveType->update($data);

        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type updated successfully.');
    }

    public function destroy(LeaveType $leaveType)
    {
        BranchScope::assertBranchAccess($leaveType->branch_id);

        if ($leaveType->leaveRequests()->exists()) {
            return back()->with('error', 'Cannot delete leave type with associated leave requests.');
        }
        if ($leaveType->balances()->exists()) {
            return back()->with('error', 'Cannot delete leave type with associated leave balances.');
        }
        $leaveType->delete();
        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type deleted successfully.');
    }
}
