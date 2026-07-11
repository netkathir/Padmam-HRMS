<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Only the Super Admin can view the Audit Log.');

        $query = AuditLog::with(['user', 'branch'])->orderByDesc('created_at');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        $logs = $query->paginate(30)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $tables = AuditLog::query()->select('table_name')->distinct()->orderBy('table_name')->pluck('table_name');

        return view('branch-admin.audit-log.index', compact('logs', 'users', 'branches', 'actions', 'tables'));
    }
}
