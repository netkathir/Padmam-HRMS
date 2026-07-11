<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Support\BranchScope;
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
        $departments = \App\Models\Department::orderBy('name')->get();
        $summary     = Attendance::where('date', $date)
            ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('attendance.index', compact('attendance', 'departments', 'summary', 'date'));
    }

    public function markForm()
    {
        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name')->with('department'))->get();
        $departments = \App\Models\Department::orderBy('name')->get();
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
            Attendance::where('is_manual', true), 'employee'
        )->with(['employee', 'markedBy'])
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

        $data['is_manual'] = true;
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
        $pending = BranchScope::scopeQueryVia(
            Attendance::where('is_manual', true)->whereNull('approved_by'), 'employee'
        )->with(['employee.department'])
            ->orderByDesc('date')
            ->paginate(20);

        return view('attendance.pending', compact('pending'));
    }

    public function approve(Request $request, Attendance $attendance)
    {
        BranchScope::assertBranchAccess($attendance->employee?->branch_id);
        $request->validate(['action' => ['required', 'in:approve,reject']]);

        if ($request->action === 'approve') {
            $attendance->update(['approved_by' => auth()->id()]);
        } else {
            $attendance->update(['is_manual' => false, 'approved_by' => null]);
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
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('attendance.report', compact('records', 'employees', 'departments', 'month', 'year'));
    }

    private function recalculate(Attendance $attendance): void
    {
        if (! $attendance->in_time || ! $attendance->out_time) return;

        $inMin  = $this->timeToMinutes($attendance->in_time);
        $outMin = $this->timeToMinutes($attendance->out_time);
        $work   = max(0, $outMin - $inMin);

        $attendance->update(['work_minutes' => $work]);
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
