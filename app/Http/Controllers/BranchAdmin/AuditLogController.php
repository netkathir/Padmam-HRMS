<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Access is governed by the branch_admin_audit_log.read permission on
        // the route (Super Admin bypasses it unconditionally, same as every
        // other module) — no separate hard-coded Super-Admin-only check here.
        // A branch-scoped viewer (e.g. a Branch Head granted this permission)
        // sees only their own branch's audit trail; Super Admin always sees
        // exactly the currently selected branch's trail (switchable via the
        // Branch Switcher — there is no "All Branches" view).
        $query = BranchScope::scopeQuery(AuditLog::with(['user', 'branch']))->orderByDesc('created_at');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped viewer
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        $logs = $query->paginate(30)->withQueryString();

        $users = BranchScope::scopeQuery(User::query())->orderBy('name')->get(['id', 'name']);
        $branches = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get(['id', 'name']) : collect();
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $tables = AuditLog::query()->select('table_name')->distinct()->orderBy('table_name')->pluck('table_name');

        return view('branch-admin.audit-log.index', compact('logs', 'users', 'branches', 'actions', 'tables'));
    }
}
