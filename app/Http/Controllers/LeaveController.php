<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PermissionRequest;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver'])
            ->orderByDesc('created_at');

        // Full-level access to Leaves means seeing every employee's requests
        // (managing the module); read/create-level access is self-service
        // only — scoped to your own leave requests. Permission-driven rather
        // than tied to specific role names, so any role granted leaves.full
        // gets manager-level visibility.
        $user = auth()->user();
        if (! $user->can('leaves.full')) {
            $query->where('employee_id', optional($user->employee)->id);
        }
        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('leave_type_id')) $query->where('leave_type_id', $request->leave_type_id);
        if ($request->filled('employee_id'))   $query->where('employee_id', $request->employee_id);

        $leaves     = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $employees  = $user->can('leaves.full') ? BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get() : collect();

        return view('leaves.index', compact('leaves', 'leaveTypes', 'employees'));
    }

    public function create()
    {
        $employee   = auth()->user()->employee;
        // FSD 7.4: "Leave types shall be available only for the selected
        // employee types" — restrict the self-service dropdown to leave
        // types applicable to this employee's classification.
        $leaveTypes = LeaveType::where('is_active', true)->get()
            ->filter(fn($lt) => $employee ? $lt->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type) : true)
            ->values();
        $balances   = $employee ? LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)->with('leaveType')->get() : collect();
        $employees  = auth()->user()->can('leaves.full')
            ? BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get()
            : collect();

        return view('leaves.create', compact('leaveTypes', 'employee', 'balances', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['required', 'string', 'max:500'],
        ]);

        $employee = auth()->user()->employee;
        if (! $employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }
        BranchScope::assertBranchIsActive($employee->branch_id);

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        if (! $leaveType->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type)) {
            return back()->with('error', 'This leave type is not applicable to your employee category.');
        }

        $totalDays = $this->countLeaveDays($data['start_date'], $data['end_date']);

        // Check balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', now()->year)->first();

        if (! $balance || $balance->balance_days < $totalDays) {
            return back()->with('error', 'Insufficient leave balance.');
        }

        // Check overlap
        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'You already have a leave request for this date range.');
        }

        DB::transaction(function () use ($data, $employee, $totalDays) {
            LeaveRequest::create(array_merge($data, [
                'employee_id' => $employee->id,
                'total_days'  => $totalDays,
                'status'      => 'pending',
                'applied_by'  => auth()->id(),
            ]));

            LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $data['leave_type_id'])
                ->where('year', now()->year)
                ->increment('pending_days', $totalDays);
        });

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted.');
    }

    public function show(LeaveRequest $leave)
    {
        BranchScope::assertBranchAccess($leave->employee?->branch_id);
        $leave->load(['employee.department', 'leaveType', 'approver']);
        return view('leaves.show', compact('leave'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        BranchScope::assertBranchAccess($leave->employee?->branch_id);

        // Fine-grained Branch Administration gate — additive, only ever
        // consulted for branch-scoped accounts; legacy/Super Admin approvals
        // via the existing role system are completely unaffected.
        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'leave', 'approve')) {
            abort(403, 'You do not have the "Approve" permission for Leave in Branch Administration.');
        }

        $request->validate([
            'action'           => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
        ]);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'This leave request has already been processed.');
        }

        DB::transaction(function () use ($request, $leave) {
            if ($request->action === 'approve') {
                $leave->update([
                    'status'      => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', now()->year)
                    ->decrement('pending_days', $leave->total_days);
                LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', now()->year)
                    ->increment('used_days', $leave->total_days);
            } else {
                $leave->update([
                    'status'           => 'rejected',
                    'approved_by'      => auth()->id(),
                    'approved_at'      => now(),
                    'rejection_reason' => $request->rejection_reason,
                ]);
                LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', now()->year)
                    ->decrement('pending_days', $leave->total_days);
            }
        });

        return redirect()->route('leaves.index')->with('success', 'Leave request ' . $request->action . 'd.');
    }

    public function cancel(LeaveRequest $leave)
    {
        BranchScope::assertBranchAccess($leave->employee?->branch_id);
        if (! in_array($leave->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel this leave request.');
        }

        DB::transaction(function () use ($leave) {
            $col = $leave->status === 'pending' ? 'pending_days' : 'used_days';
            LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->where('year', now()->year)
                ->decrement($col, $leave->total_days);

            $leave->update(['status' => 'cancelled', 'cancelled_by' => auth()->id(), 'cancelled_at' => now()]);
        });

        return back()->with('success', 'Leave request cancelled.');
    }

    public function balance()
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            return view('leaves.balance', ['balances' => collect(), 'employee' => null]);
        }

        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->with('leaveType')
            ->get();

        return view('leaves.balance', compact('balances', 'employee'));
    }

    public function permissions()
    {
        $user = auth()->user();
        $query = PermissionRequest::with(['employee', 'approver'])->orderByDesc('date');

        if (! $user->can('leaves.full')) {
            $query->where('employee_id', optional($user->employee)->id);
        }
        $query = BranchScope::scopeQueryVia($query, 'employee');

        $permissions = $query->paginate(20);
        $employees   = $user->can('leaves.full') ? BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get() : collect();
        return view('leaves.permissions', compact('permissions', 'employees'));
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'date'      => ['required', 'date'],
            'from_time' => ['required', 'date_format:H:i'],
            'to_time'   => ['required', 'date_format:H:i', 'after:from_time'],
            'reason'    => ['required', 'string', 'max:500'],
        ]);

        $employee = auth()->user()->employee;
        if (! $employee) {
            return back()->with('error', 'No employee profile linked.');
        }

        [$fh, $fm] = explode(':', $data['from_time']);
        [$th, $tm] = explode(':', $data['to_time']);
        $duration  = ((int)$th * 60 + (int)$tm) - ((int)$fh * 60 + (int)$fm);

        PermissionRequest::create(array_merge($data, [
            'employee_id'      => $employee->id,
            'duration_minutes' => $duration,
            'status'           => 'pending',
        ]));

        return redirect()->route('leaves.permissions')->with('success', 'Permission request submitted.');
    }

    private function countLeaveDays(string $from, string $to): float
    {
        $start = \Carbon\Carbon::parse($from);
        $end   = \Carbon\Carbon::parse($to);
        $days  = 0;

        while ($start->lte($end)) {
            if (! in_array($start->dayOfWeek, [0, 6])) {
                $days++;
            }
            $start->addDay();
        }

        return $days;
    }
}
