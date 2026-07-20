<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckpointController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Checkpoint::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped actor
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $checkpoints = $query->paginate(20)->withQueryString();
        $branches = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();

        return view('masters.checkpoints.index', compact('checkpoints', 'branches'));
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        return view('masters.checkpoints.create', compact('currentBranch'));
    }

    private function rules(?int $checkpointId = null, ?int $branchId = null): array
    {
        return [
            'branch_id'   => ['required', 'exists:branches,id'],
            'name'        => ['required', 'string', 'max:50', Rule::unique('checkpoints', 'name')->where('branch_id', $branchId)->ignore($checkpointId)],
            'code'        => ['required', 'string', 'max:20', Rule::unique('checkpoints', 'code')->where('branch_id', $branchId)->ignore($checkpointId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['boolean'],
        ];
    }

    public function store(Request $request)
    {
        $branchId = BranchScope::currentBranchId() ?? $request->input('branch_id');

        $data = $request->validate($this->rules(null, $branchId));
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);

        Checkpoint::create($data);

        return redirect()->route('masters.checkpoints.index')
            ->with('success', 'Checkpoint created successfully.');
    }

    public function edit(Checkpoint $checkpoint)
    {
        BranchScope::assertBranchAccess($checkpoint->branch_id);
        $currentBranch = $checkpoint->branch;

        return view('masters.checkpoints.edit', compact('checkpoint', 'currentBranch'));
    }

    public function update(Request $request, Checkpoint $checkpoint)
    {
        BranchScope::assertBranchAccess($checkpoint->branch_id);
        $branchId = BranchScope::currentBranchId() ?? $request->input('branch_id');

        $data = $request->validate($this->rules($checkpoint->id, $branchId));
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);

        $checkpoint->update($data);

        return redirect()->route('masters.checkpoints.index')
            ->with('success', 'Checkpoint updated successfully.');
    }

    public function destroy(Checkpoint $checkpoint)
    {
        BranchScope::assertBranchAccess($checkpoint->branch_id);

        if ($checkpoint->employeeCheckpoints()->exists()) {
            return back()->with('error', 'Cannot delete a checkpoint with employee mappings assigned to it.');
        }

        $checkpoint->delete();
        return redirect()->route('masters.checkpoints.index')
            ->with('success', 'Checkpoint deleted successfully.');
    }
}
