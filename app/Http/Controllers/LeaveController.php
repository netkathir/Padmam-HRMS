<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PermissionRequest;
use App\Models\Setting;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        // FSD 12.1 — "Derived or suggested based on attendance status."
        // Read-only: surfaces attendance days marked on_leave with no
        // matching leave request, for the employees this user can see.
        $suggestions = $this->attendanceLeaveSuggestions($user);

        return view('leaves.index', compact('leaves', 'leaveTypes', 'employees', 'suggestions'));
    }

    /**
     * FSD 12.1 — attendance-derived leave suggestions. Additive/read-only:
     * never creates or modifies anything, just surfaces a candidate list.
     */
    private function attendanceLeaveSuggestions($user)
    {
        $employeeIds = $user->can('leaves.full')
            ? BranchScope::scopeQuery(Employee::active())->pluck('id')
            : collect([optional($user->employee)->id])->filter();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $onLeaveAttendance = Attendance::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'on_leave')
            ->whereBetween('date', [now()->subMonthNoOverflow()->startOfMonth(), now()->endOfMonth()])
            ->orderByDesc('date')
            ->get();

        return $onLeaveAttendance->reject(function ($att) {
            return LeaveRequest::where('employee_id', $att->employee_id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_date', '<=', $att->date)
                ->where('end_date', '>=', $att->date)
                ->exists();
        })->values();
    }

    public function create(Request $request)
    {
        $employee = auth()->user()->employee;
        if ($request->filled('employee_id') && auth()->user()->can('leaves.full')) {
            $preselected = Employee::find($request->employee_id);
            if ($preselected) {
                $employee = $preselected;
            }
        }
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
            'employee_id'     => ['nullable', 'exists:employees,id'],
            'leave_type_id'   => ['required', 'exists:leave_types,id'],
            'start_date'      => ['required', 'date', 'after_or_equal:today'],
            'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day'     => ['boolean'],
            'half_day_period' => ['nullable', 'required_if:is_half_day,1', 'in:first,second'],
            'reason'          => ['required', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        // FSD 12.1 — "Entered manually by authorized users." An HR user with
        // leaves.full may file leave on behalf of another employee; anyone
        // else always files against their own linked employee, regardless
        // of what's submitted.
        $employee = ($user->can('leaves.full') && ! empty($data['employee_id']))
            ? Employee::find($data['employee_id'])
            : $user->employee;

        if (! $employee) {
            return back()->with('error', 'No employee profile linked to your account.');
        }
        BranchScope::assertBranchAccess($employee->branch_id);
        BranchScope::assertBranchIsActive($employee->branch_id);

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        if (! $leaveType->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type)) {
            return back()->with('error', 'This leave type is not applicable to your employee category.');
        }

        $isHalfDay = $request->boolean('is_half_day');
        if ($isHalfDay && $data['start_date'] !== $data['end_date']) {
            return back()->with('error', 'A half-day request must have the same start and end date.')->withInput();
        }

        $totalDays = $isHalfDay ? 0.5 : $this->countLeaveDays($data['start_date'], $data['end_date'], $employee);

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', now()->year)->first();

        $allowNegative = Setting::get('leave', 'allow_negative_balance', false);
        if ($leaveType->is_paid && ! $allowNegative && (! $balance || $balance->balance_days < $totalDays)) {
            return back()->with('error', 'Insufficient leave balance.')->withInput();
        }

        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            return back()->with('error', 'You already have a leave request for this date range.')->withInput();
        }

        DB::transaction(function () use ($data, $employee, $totalDays, $isHalfDay) {
            LeaveRequest::create([
                'employee_id'     => $employee->id,
                'leave_type_id'   => $data['leave_type_id'],
                'start_date'      => $data['start_date'],
                'end_date'        => $data['end_date'],
                'is_half_day'     => $isHalfDay,
                'half_day_period' => $data['half_day_period'] ?? null,
                'reason'          => $data['reason'],
                'total_days'      => $totalDays,
                'status'          => 'pending',
                'applied_by'      => auth()->id(),
            ]);

            LeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $data['leave_type_id'], 'year' => now()->year],
                ['allocated_days' => 0]
            )->increment('pending_days', $totalDays);
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

                $this->overrideAttendanceForApprovedLeave($leave);
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

    /**
     * Once a leave request is approved, it overrides whatever Attendance
     * status already exists for every date in its range (e.g. a biometric
     * upload had already marked a day Present or Absent before the leave
     * was approved) — Leave always wins over Attendance for the same date.
     * Each changed date is individually audit-logged (who/when/old status
     * -> new status), never silently overwritten.
     */
    private function overrideAttendanceForApprovedLeave(LeaveRequest $leave): void
    {
        $leave->loadMissing('leaveType', 'employee');
        $employee = $leave->employee;
        if (! $employee) {
            return;
        }

        $newStatus = $leave->leaveType?->is_paid ? 'paid_leave' : 'unpaid_leave';
        $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $existing = Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->first();
            $oldValues = $existing?->toArray();

            // Nothing to override — no need to touch or audit-log a date
            // that already correctly reflects this same leave.
            if ($existing && $existing->status === $newStatus && $existing->leave_type_id === $leave->leave_type_id) {
                continue;
            }

            $attendance = $existing ?: new Attendance(['employee_id' => $employee->id, 'date' => $dateStr]);
            $attendance->fill([
                'shift_id'        => $attendance->shift_id ?: $employee->shift_id,
                'status'          => $newStatus,
                'leave_type_id'   => $leave->leave_type_id,
                'in_time'         => null,
                'out_time'        => null,
                'work_minutes'    => 0,
                'is_manual_entry' => false,
                'source'          => 'manual',
            ]);
            $attendance->save();

            AuditLog::write(
                auth()->id(),
                'leave_override',
                'attendance',
                $attendance->id,
                $oldValues,
                $attendance->fresh()->toArray(),
                $employee->branch_id,
                'Leave approved (' . ($leave->leaveType->name ?? 'Leave') . ') overrides prior attendance status for ' . $dateStr
            );
        }
    }

    public function cancel(LeaveRequest $leave)
    {
        BranchScope::assertBranchAccess($leave->employee?->branch_id);

        $user = auth()->user();
        if (! $user->can('leaves.full') && optional($user->employee)->id !== $leave->employee_id) {
            abort(403, 'You can only cancel your own leave requests.');
        }

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

    /**
     * FSD 12.3 — Leave Balance grid: one row per employee/leave-type/year,
     * every FSD-listed component visible (Opening/Accrued/Used/Adjusted/
     * Lapsed/Carry Forward/Available). Full-level access sees every
     * employee (branch-scoped + department/search filters); self-service
     * sees only their own rows.
     */
    public function balance(Request $request)
    {
        $user = auth()->user();
        $year = (int) $request->input('year', now()->year);

        $query = LeaveBalance::with(['employee.department', 'leaveType', 'adjustments'])
            ->where('year', $year)
            ->whereHas('employee');

        if ($user->can('leaves.full')) {
            $query = BranchScope::scopeQueryVia($query, 'employee');
            if ($request->filled('department_id')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
            }
            if ($request->filled('search')) {
                $s = '%' . $request->search . '%';
                $query->whereHas('employee', fn($q) => $q->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)->orWhere('employee_code', 'like', $s));
            }
        } else {
            $query->where('employee_id', optional($user->employee)->id);
        }

        $balances    = $query->orderBy('employee_id')->get();
        $departments = $user->can('leaves.full') ? BranchScope::scopeQuery(Department::query())->orderBy('name')->get() : collect();

        return view('leaves.balance', compact('balances', 'departments', 'year'));
    }

    /**
     * FSD 12.3 — "Manual adjustments shall require a reason and
     * authorization" + "adjustment history shall be maintained." Reuses the
     * existing leaves.full gate (same authorization level already used for
     * approve/cancel) rather than minting a new permission.
     */
    public function adjustBalance(Request $request, LeaveBalance $balance)
    {
        BranchScope::assertBranchAccess($balance->employee?->branch_id);

        $data = $request->validate([
            'adjustment_days' => ['required', 'numeric', 'not_in:0'],
            'reason'          => ['required', 'string', 'max:500'],
        ]);

        $allowNegative = Setting::get('leave', 'allow_negative_balance', false);
        $resultingBalance = $balance->balance_days + (float) $data['adjustment_days'];
        if (! $allowNegative && $resultingBalance < 0) {
            throw ValidationException::withMessages([
                'adjustment_days' => 'This adjustment would make the leave balance negative, which is not allowed.',
            ]);
        }

        DB::transaction(function () use ($balance, $data) {
            $balance->increment('adjusted_days', $data['adjustment_days']);

            LeaveBalanceAdjustment::create([
                'leave_balance_id' => $balance->id,
                'employee_id'      => $balance->employee_id,
                'leave_type_id'    => $balance->leave_type_id,
                'adjustment_days'  => $data['adjustment_days'],
                'reason'           => $data['reason'],
                'adjusted_by'      => auth()->id(),
            ]);
        });

        return back()->with('success', 'Leave balance adjusted.');
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

    /** Fixes a pre-existing bug: the view posted this action to leaves.approve (LeaveRequest). */
    public function approvePermission(Request $request, PermissionRequest $permission)
    {
        BranchScope::assertBranchAccess($permission->employee?->branch_id);

        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'leave', 'approve')) {
            abort(403, 'You do not have the "Approve" permission for Leave in Branch Administration.');
        }

        $request->validate([
            'action'           => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
        ]);

        if ($permission->status !== 'pending') {
            return back()->with('error', 'This permission request has already been processed.');
        }

        $permission->update($request->action === 'approve'
            ? ['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]
            : ['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now(), 'rejection_reason' => $request->rejection_reason]);

        return back()->with('success', 'Permission request ' . $request->action . 'd.');
    }

    /**
     * FSD 12.1/12.3 day counting — excludes weekends AND active holidays
     * (Module 3 `Holiday` master, branch/employee-type aware) applicable to
     * this employee. Half-day requests are handled separately by the caller
     * (always 0.5, never routed through this weekday/holiday loop).
     */
    private function countLeaveDays(string $from, string $to, Employee $employee): float
    {
        $start = \Carbon\Carbon::parse($from);
        $end   = \Carbon\Carbon::parse($to);

        $holidayDates = [];
        Holiday::where('is_active', true)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get()
            ->filter(fn($h) => $h->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type))
            ->each(function ($h) use (&$holidayDates) {
                for ($d = $h->start_date->copy(); $d->lte($h->end_date); $d->addDay()) {
                    $holidayDates[] = $d->toDateString();
                }
            });

        $days = 0;
        while ($start->lte($end)) {
            if (! in_array($start->dayOfWeek, [0, 6]) && ! in_array($start->toDateString(), $holidayDates, true)) {
                $days++;
            }
            $start->addDay();
        }

        return $days;
    }
}
