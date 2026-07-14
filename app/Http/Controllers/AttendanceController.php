<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\BusinessRule;
use App\Models\Employee;
use App\Models\Holiday;
use App\Support\BranchScope;
use App\Support\RuleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $query = Attendance::with(['employee.department', 'employee.branch'])
            ->where('date', $date)
            ->orderBy('in_time');

        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance  = $query->paginate(25)->withQueryString();
        $departments = BranchScope::scopeQuery(\App\Models\Department::query())->orderBy('name')->get();
        $summary     = BranchScope::scopeQueryVia(Attendance::where('date', $date), 'employee')
            ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('attendance.index', compact('attendance', 'departments', 'summary', 'date'));
    }

    public function markForm()
    {
        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name')->with('department'))->get();
        $departments = BranchScope::scopeQuery(\App\Models\Department::query())->orderBy('name')->get();
        return view('attendance.mark', compact('employees', 'departments'));
    }

    public function mark(Request $request)
    {
        $request->validate([
            'date'                       => ['required', 'date'],
            'attendance'                 => ['required', 'array'],
            'attendance.*.employee_id'   => ['required', 'exists:employees,id'],
            'attendance.*.status'        => ['required', 'in:present,absent,half_day,late,holiday,leave,work_from_home'],
            'attendance.*.in_time'       => ['nullable', 'date_format:H:i'],
            'attendance.*.out_time'      => ['nullable', 'date_format:H:i'],
            'attendance.*.remarks'       => ['nullable', 'string', 'max:255'],
        ]);

        $date = $request->date;
        $saved = 0;

        $scopedBranchId = BranchScope::currentBranchId();

        foreach ($request->attendance as $entry) {
            $employee = Employee::find($entry['employee_id']);
            if (! $employee) continue;
            if ($scopedBranchId !== null && $employee->branch_id !== $scopedBranchId) continue;
            if ($employee->branch && ! $employee->branch->is_active) continue;

            $data = [
                'employee_id' => $entry['employee_id'],
                'date'        => $date,
                'status'      => $entry['status'],
                'in_time'     => $entry['in_time'] ? $date . ' ' . $entry['in_time'] . ':00' : null,
                'out_time'    => $entry['out_time'] ? $date . ' ' . $entry['out_time'] . ':00' : null,
                'remarks'     => $entry['remarks'] ?? null,
                'shift_id'    => $employee->shift_id,
            ];

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $entry['employee_id'], 'date' => $date],
                $data
            );

            if ($attendance->in_time && $attendance->out_time) {
                $this->recalculate($attendance);
            }

            $saved++;
        }

        return redirect()->route('attendance.index', ['date' => $date])
            ->with('success', "Attendance saved for {$saved} employee(s).");
    }

    public function manualForm()
    {
        $employees = BranchScope::scopeQuery(Employee::active()->orderBy('first_name')->with('department'))->get();
        $recentEntries = BranchScope::scopeQueryVia(
            Attendance::where('is_manual_entry', true), 'employee'
        )->with('employee')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        return view('attendance.manual', compact('employees', 'recentEntries'));
    }

    public function manual(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'date'          => ['required', 'date', 'before_or_equal:today'],
            'in_time'       => ['required', 'date_format:H:i'],
            'out_time'      => ['nullable', 'date_format:H:i', 'after:in_time'],
            'status'        => ['required', 'in:present,half_day,absent,on_leave,holiday,week_off'],
            'manual_reason' => ['required', 'string', 'max:255'],
        ]);

        $manualEmployee = Employee::find($data['employee_id']);
        BranchScope::assertBranchAccess($manualEmployee?->branch_id);
        BranchScope::assertBranchIsActive($manualEmployee?->branch_id);

        // There is no dedicated `manual_reason` column — it's recorded in
        // the general-purpose `remarks` field that already exists on this
        // table.
        $data['remarks'] = $data['manual_reason'];
        unset($data['manual_reason']);

        $data['is_manual_entry'] = true;
        $data['approval_status'] = 'pending';
        $data['source']    = 'manual';
        $data['in_time']   = $data['date'] . ' ' . $data['in_time'] . ':00';
        $data['out_time']  = ! empty($data['out_time']) ? $data['date'] . ' ' . $data['out_time'] . ':00' : null;

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );

        if ($attendance->in_time && $attendance->out_time) {
            $this->recalculate($attendance);
        }

        return redirect()->route('attendance.pending')
            ->with('success', 'Manual attendance submitted for approval.');
    }

    public function pending()
    {
        $pendingRequests = BranchScope::scopeQueryVia(
            Attendance::where('is_manual_entry', true)->where('approval_status', 'pending'), 'employee'
        )->with(['employee.department'])
            ->orderByDesc('date')
            ->paginate(20);

        return view('attendance.pending', compact('pendingRequests'));
    }

    public function approve(Request $request, Attendance $attendance)
    {
        BranchScope::assertBranchAccess($attendance->employee?->branch_id);
        $request->validate(['action' => ['required', 'in:approve,reject']]);

        if ($request->action === 'approve') {
            $attendance->update(['approved_by' => auth()->id(), 'approved_at' => now(), 'approval_status' => 'approved']);
        } else {
            $attendance->update(['is_manual_entry' => false, 'approved_by' => null, 'approval_status' => 'rejected']);
        }

        return back()->with('success', 'Attendance ' . $request->action . 'd.');
    }

    public function report(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $records = BranchScope::scopeQueryVia(
            Attendance::with(['employee.department'])
                ->whereMonth('date', $month)
                ->whereYear('date', $year),
            'employee'
        )
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderBy('date')
            ->paginate(30)->withQueryString();

        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $departments = BranchScope::scopeQuery(\App\Models\Department::query())->orderBy('name')->get();

        return view('attendance.report', compact('records', 'employees', 'departments', 'month', 'year'));
    }

    /**
     * Module 4 FSD 8.4 — Attendance Rule. This is called for every
     * mark()/manual() save exactly as before this feature existed; the only
     * behavior change is additive (grace/rounding/late/OT/full-half-day
     * computation) and only when an Attendance Rule actually resolves for
     * the employee's context — no rule configured means this method behaves
     * byte-for-byte as it did previously (work_minutes = out - in, nothing
     * else touched).
     *
     * Guardrail (FSD: "shall not silently replace manually corrected
     * attendance"): entries submitted through the dedicated manual()
     * correction flow are flagged is_manual_entry=true and are NEVER
     * touched here — only mark()'s routine/bulk entries (is_manual_entry
     * stays false) are auto-computed/corrected by the rule engine, which is
     * the intended automation target, not a "correction."
     */
    private function recalculate(Attendance $attendance): void
    {
        if (! $attendance->in_time || ! $attendance->out_time) {
            $this->applyIncompletePunchTreatment($attendance);
            return;
        }

        $inMin  = $this->timeToMinutes($attendance->in_time);
        $outMin = $this->timeToMinutes($attendance->out_time);
        $work   = max(0, $outMin - $inMin);

        $updates = ['work_minutes' => $work];

        if (! $attendance->is_manual_entry) {
            $employee = $attendance->employee ?? Employee::find($attendance->employee_id);
            $rule = $employee ? BusinessRule::resolveForEmployee(
                $employee, 'attendance', $employee->branch_id, $employee->primary_employee_type,
                $employee->labour_type, $employee->contractor_id, $attendance->date->toDateString()
            ) : null;
            $detail = $rule?->attendanceRule;

            if ($detail && $detail->appliesToShift($attendance->shift_id)) {
                if ($detail->rounding_minutes) {
                    $work = RuleEngine::roundMinutes($work, $detail->rounding_minutes);
                    $updates['work_minutes'] = $work;
                }

                $shift = $attendance->shift;
                if ($shift) {
                    $shiftStartMin = $this->timeToMinutes($shift->start_time);
                    $shiftEndMin   = $this->timeToMinutes($shift->end_time);

                    $lateMin  = max(0, $inMin - $shiftStartMin - $detail->late_grace_minutes);
                    $earlyMin = max(0, $shiftEndMin - $outMin - $detail->early_exit_grace_minutes);
                    $updates['is_late'] = $lateMin > 0;
                    $updates['late_minutes'] = $lateMin;
                    $updates['is_early_exit'] = $earlyMin > 0;
                    $updates['early_exit_minutes'] = $earlyMin;
                    $updates['ot_minutes'] = max(0, $outMin - $shiftEndMin);
                }

                $workHours = $work / 60;
                if ($workHours >= $detail->min_full_day_hours) {
                    $updates['status'] = 'present';
                } elseif ($workHours >= $detail->min_half_day_hours) {
                    $updates['status'] = 'half_day';
                } else {
                    $updates['status'] = 'absent';
                }

                $updates['applied_rules'] = array_merge($attendance->applied_rules ?? [], ['attendance' => $rule->id]);
            }
        }

        $attendance->update($updates);
    }

    /**
     * FSD 8.4 — Missing Punch Treatment / Single Punch Treatment. Only
     * applied once the attendance date has passed (a same-day/future record
     * with an incomplete punch just hasn't finished its day yet) and never
     * for manually-corrected entries.
     */
    private function applyIncompletePunchTreatment(Attendance $attendance): void
    {
        if ($attendance->is_manual_entry) {
            return;
        }
        if (! $attendance->date->lt(now()->startOfDay())) {
            return; // only a genuinely past day — today is still in progress
        }

        $employee = $attendance->employee ?? Employee::find($attendance->employee_id);
        if (! $employee) {
            return;
        }

        $rule = BusinessRule::resolveForEmployee(
            $employee, 'attendance', $employee->branch_id, $employee->primary_employee_type,
            $employee->labour_type, $employee->contractor_id, $attendance->date->toDateString()
        );
        $detail = $rule?->attendanceRule;
        if (! $detail || ! $detail->appliesToShift($attendance->shift_id)) {
            return;
        }

        $hasOnePunch = (bool) ($attendance->in_time xor $attendance->out_time);
        $treatment = $hasOnePunch ? $detail->single_punch_treatment : $detail->missing_punch_treatment;

        if (! in_array($treatment, ['absent', 'half_day'], true)) {
            return; // pending_review — leave status untouched for manual review
        }

        $attendance->update([
            'status' => $treatment,
            'applied_rules' => array_merge($attendance->applied_rules ?? [], ['attendance' => $rule->id]),
        ]);
    }

    private function timeToMinutes(string $time): int
    {
        // Handle both 'H:i' and 'Y-m-d H:i:s' formats
        $parts = explode(' ', $time);
        $timeStr = end($parts);
        [$h, $m] = explode(':', $timeStr);
        return (int)$h * 60 + (int)$m;
    }
}
