<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class BranchSwitcherController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Only the Super Admin can use the Branch Switcher.');
    }

    public function index()
    {
        $this->ensureSuperAdmin();

        $branches = Branch::active()->orderBy('name')->get();
        // Resolves (and persists, if unset) the effective branch — there is
        // no "All Branches" mode, a Super Admin always has one selected.
        $currentBranchId = BranchScope::currentBranchId();

        return view('branch-admin.branch-switcher.index', compact('branches', 'currentBranchId'));
    }

    public function switch(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        abort_if(! $branch->is_active, 422, 'Cannot switch to an inactive branch.');

        session(['current_branch_id' => $branch->id]);
        AuditLog::write(auth()->id(), 'switch', 'branches', $branch->id, null, ['branch_id' => $branch->id], $branch->id);

        return back()->with('success', "Switched to {$branch->name}.");
    }
}
