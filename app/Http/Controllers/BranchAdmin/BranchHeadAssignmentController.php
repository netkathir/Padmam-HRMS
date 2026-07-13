<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchHeadAssignment;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class BranchHeadAssignmentController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Only the Super Admin can assign Branch Heads.');
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        // Strict branch-wise filtering — always the currently selected
        // branch (switchable via the Branch Switcher), never "All Branches",
        // consistent with every other module in the app.
        $query = BranchScope::scopeQuery(BranchHeadAssignment::with(['branch', 'user']))
            ->orderByDesc('effective_from');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->paginate(20)->withQueryString();

        return view('branch-admin.head-assignments.index', compact('assignments'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        // Branch is always the currently selected one — never a free pick,
        // consistent with Users/Employees (see BranchScope::stampBranchId()
        // in store() below, which enforces this server-side too).
        $currentBranchId = BranchScope::currentBranchId();
        $branches = $currentBranchId
            ? Branch::where('id', $currentBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        // A Branch Head cannot be another Branch Head's target of confusion with
        // Super Admin — exclude existing super_admin-role users from the picker.
        $users = User::whereHas('role', fn($q) => $q->where('name', '!=', 'super_admin'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('branch-admin.head-assignments.create', compact('branches', 'users') + [
            'lockedBranchId' => $currentBranchId,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $this->validateAssignment($request);

        // Force branch_id to the currently selected branch, ignoring
        // whatever was submitted — the same guardrail every other
        // create/store path in the app applies (Users, Employees, etc.).
        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id']);

        $actor = auth()->user();

        $targetUser = User::findOrFail($data['user_id']);
        abort_if($targetUser->role?->name === 'super_admin', 422, 'A Super Admin cannot be assigned as a Branch Head.');

        if ($data['status'] === 'active') {
            $assignment = BranchHeadAssignment::assign($data, $actor->id);
        } else {
            // Recorded inactive from the outset — doesn't touch any existing
            // active assignment or the target user's branch-head scoping.
            $assignment = BranchHeadAssignment::create(array_merge($data, [
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
            ]));
        }

        AuditLog::write($actor->id, 'assign', 'branch_head_assignments', $assignment->id, null, $data, $data['branch_id']);

        return redirect()->route('branch-admin.head-assignments.index')->with('success', 'Branch Head assigned successfully.');
    }

    public function edit(BranchHeadAssignment $headAssignment)
    {
        $this->ensureSuperAdmin();
        BranchScope::assertBranchAccess($headAssignment->branch_id);

        return view('branch-admin.head-assignments.edit', ['assignment' => $headAssignment]);
    }

    public function update(Request $request, BranchHeadAssignment $headAssignment)
    {
        $this->ensureSuperAdmin();
        BranchScope::assertBranchAccess($headAssignment->branch_id);

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status'         => ['required', 'in:active,inactive'],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ]);

        $wasActive = $headAssignment->status === 'active';
        $oldValues = $headAssignment->only(array_keys($data));

        if (! $wasActive && $data['status'] === 'active') {
            // Reactivating this record — deactivate any other active
            // assignment for the branch first (single-active-head guarantee),
            // then restore this user's branch-head scoping directly.
            BranchHeadAssignment::where('branch_id', $headAssignment->branch_id)
                ->where('id', '!=', $headAssignment->id)
                ->active()
                ->update(['status' => 'inactive', 'effective_to' => now()->toDateString()]);
            $headAssignment->update($data);
            $headAssignment->user->update([
                'user_type' => 'branch_head',
                'branch_id' => $headAssignment->branch_id,
                'updated_by' => auth()->id(),
            ]);
            BranchHeadAssignment::ensureOperationalRole($headAssignment->user_id, auth()->id());
        } else {
            $headAssignment->update($data);

            if ($wasActive && $data['status'] === 'inactive') {
                BranchHeadAssignment::release($headAssignment->user_id, auth()->id());
            }
        }

        AuditLog::write(auth()->id(), 'update', 'branch_head_assignments', $headAssignment->id, $oldValues, $data, $headAssignment->branch_id);

        return redirect()->route('branch-admin.head-assignments.index')->with('success', 'Assignment updated.');
    }

    public function deactivate(BranchHeadAssignment $headAssignment)
    {
        $this->ensureSuperAdmin();
        BranchScope::assertBranchAccess($headAssignment->branch_id);

        $headAssignment->update(['status' => 'inactive', 'effective_to' => now()->toDateString()]);
        BranchHeadAssignment::release($headAssignment->user_id, auth()->id());

        AuditLog::write(auth()->id(), 'deactivate', 'branch_head_assignments', $headAssignment->id, null, null, $headAssignment->branch_id);

        return back()->with('success', 'Branch Head assignment deactivated.');
    }

    public function destroy(BranchHeadAssignment $headAssignment)
    {
        $this->ensureSuperAdmin();
        BranchScope::assertBranchAccess($headAssignment->branch_id);

        if ($headAssignment->status === 'active') {
            return back()->with('error', 'Deactivate this assignment before deleting it.');
        }

        $headAssignment->delete();
        AuditLog::write(auth()->id(), 'delete', 'branch_head_assignments', $headAssignment->id, null, null, $headAssignment->branch_id);

        return redirect()->route('branch-admin.head-assignments.index')->with('success', 'Assignment record deleted.');
    }

    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'branch_id'      => ['required', 'exists:branches,id'],
            'user_id'        => ['required', 'exists:users,id'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status'         => ['required', 'in:active,inactive'],
            'remarks'        => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
