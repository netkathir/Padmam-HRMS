<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
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
        $currentBranchId = session('current_branch_id');

        return view('branch-admin.branch-switcher.index', compact('branches', 'currentBranchId'));
    }

    public function switch(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        session(['current_branch_id' => $data['branch_id'] ?? null]);

        $branch = $data['branch_id'] ? Branch::find($data['branch_id']) : null;
        AuditLog::write(auth()->id(), 'switch', 'branches', $data['branch_id'] ?? '', null, ['branch_id' => $data['branch_id'] ?? null], $data['branch_id'] ?? null);

        return back()->with('success', $branch ? "Switched to {$branch->name}." : 'Switched to All Branches.');
    }

    public function clear()
    {
        $this->ensureSuperAdmin();

        session()->forget('current_branch_id');
        AuditLog::write(auth()->id(), 'switch', 'branches', '', null, ['branch_id' => null]);

        return back()->with('success', 'Switched to All Branches.');
    }
}
