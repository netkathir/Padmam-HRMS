<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\BiometricUpload;
use App\Models\Branch;
use App\Models\BusinessRule;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\DepartmentWorkAssignment;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Services\BiometricUploadService;
use App\Support\BranchAdminPermissions;
use App\Support\BranchScope;
use App\Support\RuleEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * Attendance Register — per-employee summary for the selected date
     * range (exact column set per user spec): Employee, Shift, OT Hrs,
     * Total Working Days, Total Days Present, Total Days Absent, Absent
     * Leave, Casual Leave, Sick Leave, Weekend Leave, Public Leave. Only
     * these two specific leave types get their own column — NOT every
     * active LeaveType — per explicit user correction. "Total Days Absent"
     * and "Absent Leave" are deliberately kept as two separate counts for
     * now (no record at all vs. unpaid leave taken) — the user asked to
     * keep both and prune later if one turns out redundant, rather than
     * deciding that now.
     */
    public function index(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to   = $request->input('to_date', now()->toDateString());

        // LeaveType is now per-branch (see LeaveTypeController), so a Super
        // Admin viewing multiple branches at once will see one "Casual
        // Leave" row PER BRANCH here, each with a different id — grouping
        // below is therefore keyed by NAME, not id, so the two fixed
        // columns still line up across branches.
        $leaveTypes = LeaveType::where('is_active', true)->whereIn('name', ['Casual Leave', 'Sick Leave'])->orderBy('name')->get();
        $leaveTypeNames = $leaveTypes->pluck('name')->unique()->values();

        $query = Attendance::whereBetween('date', [$from, $to]);
        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($request->filled('branch_id') && BranchScope::currentBranchId() === null) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('employee_number')) {
            $s = '%' . $request->employee_number . '%';
            $query->whereHas('employee', fn($q) => $q->where('employee_code', 'like', $s));
        }
        if ($request->filled('employee_name')) {
            $s = '%' . $request->employee_name . '%';
            $query->whereHas('employee', fn($q) => $q->where('first_name', 'like', $s)->orWhere('last_name', 'like', $s));
        }
        if ($request->filled('employee_type_id')) {
            $query->whereHas('employee', fn($q) => $q->where('employee_type_id', $request->employee_type_id));
        }
        if ($request->filled('labour_type')) {
            $query->whereHas('employee', fn($q) => $q->where('labour_type', $request->labour_type));
        }
        if ($request->filled('contractor_id')) {
            $query->whereHas('employee', fn($q) => $q->where('contractor_id', $request->contractor_id));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance = $query->with('employee.department', 'employee.shift', 'employee.branch', 'leaveType')->get()
            ->groupBy('employee_id')
            ->map(function ($rows) use ($leaveTypeNames, $from, $to) {
                $employee = $rows->first()->employee;

                $leaveCounts = $leaveTypeNames->mapWithKeys(function ($name) use ($rows) {
                    return [$name => $rows->filter(fn ($r) => $r->leaveType?->name === $name)
                        ->whereIn('status', ['paid_leave', 'unpaid_leave', 'on_leave'])->count()];
                });

                return [
                    'employee'        => $employee,
                    'shift'           => $employee->shift,
                    'ot_hours'        => round($rows->sum('ot_minutes') / 60, 2),
                    'working_days'    => $this->countWorkingDaysInRange($employee, $from, $to),
                    'present'         => $rows->where('status', 'present')->count(),
                    'absent'          => $rows->whereIn('status', ['absent', 'missing_punch'])->count(),
                    'absent_leave'    => $rows->where('status', 'unpaid_leave')->count(),
                    'leave_counts'    => $leaveCounts,
                    'weekend'         => $rows->where('status', 'weekly_off')->count(),
                    'public_holiday'  => $rows->whereIn('status', ['paid_holiday', 'unpaid_holiday'])->count(),
                ];
            })->values();

        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        $employeeTypes = EmployeeType::where('is_active', true)->get();
        $contractors = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get();
        $shifts = Shift::where('is_active', true)->get();
        $currentBranchId = BranchScope::currentBranchId();
        $branches = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();

        if ($request->filled('export')) {
            $header = ['Employee Number', 'Employee', 'Shift', 'OT Hrs', 'Total Working Days', 'Total Days Present', 'Total Days Absent', 'Absent Leave'];
            foreach ($leaveTypeNames as $name) { $header[] = $name; }
            $header[] = 'Weekend Leave';
            $header[] = 'Public Leave';

            return $this->streamCsv('attendance-register-' . $from . '-to-' . $to . '.csv', $header,
                $attendance->map(function ($r) use ($leaveTypeNames) {
                    $row = [
                        $r['employee']->employee_code ?? '', $r['employee']->full_name ?? '',
                        $r['shift']->name ?? '—', $r['ot_hours'], $r['working_days'], $r['present'], $r['absent'], $r['absent_leave'],
                    ];
                    foreach ($leaveTypeNames as $name) { $row[] = $r['leave_counts'][$name] ?? 0; }
                    $row[] = $r['weekend'];
                    $row[] = $r['public_holiday'];
                    return $row;
                })->all()
            );
        }

        return view('attendance.index', compact(
            'attendance', 'departments', 'from', 'to', 'employeeTypes', 'leaveTypeNames',
            'contractors', 'shifts', 'branches', 'currentBranchId'
        ));
    }

    /**
     * Total working days (calendar days in the range minus weekly-offs)
     * for this employee's branch — mirrors PayrollController::getWorkingDays()'s
     * own weekly-off resolution (Branch::weekly_off_days, falling back to
     * Sat/Sun) but doesn't share that private method across controllers;
     * kept intentionally simple since this is a display-only summary, not
     * a payroll calculation.
     */
    private function countWorkingDaysInRange(Employee $employee, string $from, string $to): int
    {
        $dayNameToIndex = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $offIndexes = $employee->branch?->weekly_off_days
            ? array_values(array_filter(array_map(fn($d) => $dayNameToIndex[$d] ?? null, $employee->branch->weekly_off_days), fn($v) => $v !== null))
            : [0, 6];

        $start = \Carbon\Carbon::parse($from);
        $end = \Carbon\Carbon::parse($to);

        $days = 0;
        $date = $start->copy();
        while ($date->lte($end)) {
            if (! in_array($date->dayOfWeek, $offIndexes, true)) $days++;
            $date->addDay();
        }

        return $days;
    }

    public function show(Attendance $attendance)
    {
        BranchScope::assertBranchAccess($attendance->employee?->branch_id);
        $attendance->load(['employee.department', 'employee.designation', 'shift', 'leaveType', 'approver', 'otApprover', 'logs']);
        return view('attendance.show', compact('attendance'));
    }

    public function markForm()
    {
        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name')->with('department'))->get();
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
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
                'source'      => 'web',
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

        return redirect()->route('attendance.index', ['from_date' => $date, 'to_date' => $date])
            ->with('success', "Attendance saved for {$saved} employee(s).");
    }

    public function report(Request $request)
    {
        // The view's <input type="month"> submits a single "Y-m" value, not
        // separate month/year fields — parsed here instead of passed
        // straight to whereMonth()/whereYear() (which would silently
        // mismatch against a raw "2026-07" string).
        $period = \Carbon\Carbon::parse($request->input('month', now()->format('Y-m')) . '-01');
        $month = $period->month;
        $year  = $period->year;

        $query = BranchScope::scopeQueryVia(
            Attendance::whereMonth('date', $month)->whereYear('date', $year),
            'employee'
        )
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        // Per-employee monthly rollup (Present/Absent/Late/Half-Day counts) —
        // fixes a pre-existing bug where this controller never actually
        // built the `$report` variable the view expects, only a flat
        // paginated `$records` list.
        $report = (clone $query)->with('employee.department')->get()
            ->groupBy('employee_id')
            ->map(function ($rows) {
                return [
                    'employee'  => $rows->first()->employee,
                    'present'   => $rows->whereIn('status', ['present'])->count(),
                    'absent'    => $rows->whereIn('status', ['absent', 'missing_punch'])->count(),
                    'half_day'  => $rows->where('status', 'half_day')->count(),
                    'leave'     => $rows->whereIn('status', ['on_leave', 'paid_leave', 'unpaid_leave'])->count(),
                    'wfh'       => $rows->where('status', 'work_from_home')->count(),
                    'late'      => $rows->where('is_late', true)->count(),
                    'total'     => $rows->count(),
                ];
            })->values();

        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->streamCsv('attendance-monthly-report-' . $month . '-' . $year . '.csv',
                ['Employee Number', 'Employee', 'Present', 'Absent', 'Half Day', 'Leave', 'WFH', 'Late', 'Total'],
                $report->map(fn($r) => [
                    $r['employee']->employee_code ?? '', $r['employee']->full_name ?? '',
                    $r['present'], $r['absent'], $r['half_day'], $r['leave'], $r['wfh'], $r['late'], $r['total'],
                ])->all()
            );
        }

        return view('attendance.report', compact('report', 'employees', 'departments', 'month', 'year'));
    }

    /**
     * Same per-employee monthly rollup as report() (Present/Absent/Late/
     * Half-Day/Leave/WFH/Total/%) — kept as its own named page/route
     * (attendance.summary) per user request, rather than a duplicate of
     * report() the user has to navigate to a second way. The richer
     * per-leave-type / OT / working-days breakdown that used to live here
     * was moved to the Attendance Register (see index()) instead.
     */
    public function summary(Request $request)
    {
        $period = \Carbon\Carbon::parse($request->input('month', now()->format('Y-m')) . '-01');
        $month = $period->month;
        $year  = $period->year;

        $query = BranchScope::scopeQueryVia(
            Attendance::whereMonth('date', $month)->whereYear('date', $year),
            'employee'
        )
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        $summary = (clone $query)->with('employee.department')->get()
            ->groupBy('employee_id')
            ->map(function ($rows) {
                return [
                    'employee'  => $rows->first()->employee,
                    'present'   => $rows->whereIn('status', ['present'])->count(),
                    'absent'    => $rows->whereIn('status', ['absent', 'missing_punch'])->count(),
                    'half_day'  => $rows->where('status', 'half_day')->count(),
                    'leave'     => $rows->whereIn('status', ['on_leave', 'paid_leave', 'unpaid_leave'])->count(),
                    'wfh'       => $rows->where('status', 'work_from_home')->count(),
                    'late'      => $rows->where('is_late', true)->count(),
                    'total'     => $rows->count(),
                ];
            })->values();

        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->streamCsv('attendance-summary-' . $month . '-' . $year . '.csv',
                ['Employee Number', 'Employee', 'Present', 'Absent', 'Half Day', 'Leave', 'WFH', 'Late', 'Total'],
                $summary->map(fn($r) => [
                    $r['employee']->employee_code ?? '', $r['employee']->full_name ?? '',
                    $r['present'], $r['absent'], $r['half_day'], $r['leave'], $r['wfh'], $r['late'], $r['total'],
                ])->all()
            );
        }

        return view('attendance.summary', ['report' => $summary, 'employees' => $employees, 'departments' => $departments, 'month' => $month, 'year' => $year]);
    }

    // ── 11.2 Biometric Excel Upload ───────────────────────────────────────

    /**
     * Straight file-picker upload — no Branch/Period form. Branch is always
     * the currently active one (Branch Switcher / the user's own branch);
     * Period is derived silently from the file's own punch times in
     * upload() below, since this is a bulk dump of raw punches, not
     * something scoped to a reporting period by the user.
     */
    public function uploadForm()
    {
        return view('attendance.upload', ['upload' => null, 'sheetNames' => [], 'selectedSheet' => null, 'preview' => null]);
    }

    public function upload(Request $request, BiometricUploadService $service)
    {
        $branchId = BranchScope::currentBranch()?->id;
        abort_unless($branchId, 422, 'Select a branch (via the Branch Switcher) before uploading biometric data.');

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv,txt'],
        ]);

        $path = $request->file('file')->store('biometric-uploads');
        $absolutePath = Storage::path($path);

        $sheetNames = $service->sheetNames($absolutePath);
        $sheetName = $sheetNames[0] ?? null;
        [$periodFrom, $periodTo] = $sheetName ? $service->derivePeriod($absolutePath, $sheetName) : [null, null];

        $upload = BiometricUpload::create([
            'branch_id'         => $branchId,
            'period_from'       => $periodFrom ?? now()->toDateString(),
            'period_to'         => $periodTo ?? now()->toDateString(),
            'file_path'         => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'sheet_name'        => $sheetName,
            'uploaded_by'       => auth()->id(),
            'status'            => 'mapping',
        ]);

        // FSD 11.2 — "Users shall be warned before re-uploading data for an
        // already processed period." Non-blocking — upload still proceeds.
        $alreadyProcessed = $periodFrom && $periodTo && Attendance::whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date', [$periodFrom, $periodTo])
            ->exists();

        if ($alreadyProcessed) {
            session()->flash('warning', 'Attendance already exists for part of this period — re-uploading may create duplicate punches for review.');
        }

        return redirect()->route('attendance.upload.mapping', $upload);
    }

    /**
     * Preview/confirmation popup, shown on top of the same upload page — the
     * device export's column layout is fixed (Person ID, Name, Department,
     * Time, Attendance Status, Attendance Check Point, ...), so there's no
     * column mapping to choose anymore; this just lets the user pick the
     * sheet (if the workbook has more than one) and confirm before the
     * actual import runs.
     */
    public function mappingForm(Request $request, BiometricUpload $upload, BiometricUploadService $service)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        $absolutePath = Storage::path($upload->file_path);

        $sheetNames = $service->sheetNames($absolutePath);
        $selectedSheet = $request->input('sheet_name', $upload->sheet_name ?: ($sheetNames[0] ?? null));

        // Not persisted here — only used in-memory so preview() reads the
        // sheet the user actually has selected right now; confirmMapping()
        // is what actually saves this once the user clicks Confirm.
        $upload->sheet_name = $selectedSheet;
        $preview = $selectedSheet ? $service->preview($upload) : null;

        return view('attendance.upload', compact('upload', 'sheetNames', 'selectedSheet', 'preview'));
    }

    public function confirmMapping(Request $request, BiometricUpload $upload, BiometricUploadService $service)
    {
        BranchScope::assertBranchAccess($upload->branch_id);

        $data = $request->validate([
            'sheet_name' => ['required', 'string'],
        ]);

        $upload->update(['sheet_name' => $data['sheet_name'], 'status' => 'processing']);

        [$counts, $errors, $wrongBranch] = $service->validateAndImport($upload);
        $errorFilePath = $service->generateErrorFile($upload, $errors, $wrongBranch);

        $upload->update(array_merge($counts, ['error_file_path' => $errorFilePath, 'status' => 'completed']));

        // No separate "Process Attendance" step anymore — compute daily
        // Attendance rows immediately, for exactly the employees/dates this
        // upload actually imported (not the whole branch), using the file's
        // own date range (period_from/period_to, already derived from its
        // punch times in upload()).
        $importedLogs = AttendanceLog::where('biometric_upload_id', $upload->id)->get();
        if ($importedLogs->isNotEmpty()) {
            $employees = Employee::whereIn('id', $importedLogs->pluck('employee_id')->unique())->get();
            $period = \Carbon\CarbonPeriod::create($upload->period_from, $upload->period_to);
            $this->computeAttendanceForEmployees($employees, $period);
        }

        // FSD (bulk-upload follow-up) — company staff (the only employees
        // eligible for leave) get a chance to mark any missing-attendance
        // date as paid leave, so it's excluded from LOP, before payroll ever
        // sees it. Skipped entirely when the branch has no company staff.
        $hasStaff = Employee::where('branch_id', $upload->branch_id)->where('primary_employee_type', 'staff')->exists();
        if ($hasStaff) {
            return redirect()->route('attendance.upload.leave-review', $upload)->with('success', 'Upload processed and attendance computed.');
        }

        return redirect()->route('attendance.upload.summary', $upload)->with('success', 'Upload processed and attendance computed.');
    }

    public function uploadSummary(BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        return view('attendance.upload-summary', compact('upload'));
    }

    /**
     * Post-upload paid-leave confirmation — for every company staff
     * employee, lists dates in this upload's period with NO Attendance row
     * at all (after excluding Holidays and weekly-off days, which are never
     * LOP candidates in the first place). For each such date, shows the
     * matching LeaveRequest's leave type if one covers it, or a
     * "Non-Created Leave" placeholder if none exists — the admin checks a
     * date to mark it paid leave (excluded from LOP); unchecked dates are
     * left untouched and flow into LOP the same way they do today.
     */
    public function leaveReviewForm(BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);

        $staff = Employee::where('branch_id', $upload->branch_id)
            ->where('primary_employee_type', 'staff')
            ->orderBy('first_name')
            ->get();

        $period = \Carbon\CarbonPeriod::create($upload->period_from, $upload->period_to);
        $branch = $upload->branch;

        $staffGaps = $staff->map(function (Employee $employee) use ($period, $branch) {
            $gaps = [];
            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                if ($branch && $this->resolveHolidayOrWeeklyOffStatus($employee, $branch, $date)) {
                    continue; // Holiday / weekly-off — never a LOP candidate.
                }
                if (Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->exists()) {
                    continue; // Already has a concrete status — not this screen's concern.
                }

                $leave = LeaveRequest::coveringDate($dateStr)
                    ->where('employee_id', $employee->id)
                    ->whereIn('status', ['approved', 'pending'])
                    ->with('leaveType')
                    ->first();

                $gaps[] = [
                    'date' => $dateStr,
                    'leave_type_id' => $leave?->leave_type_id,
                    'leave_type_name' => $leave?->leaveType?->name,
                ];
            }

            return ['employee' => $employee, 'gaps' => $gaps];
        })->filter(fn($row) => ! empty($row['gaps']))->values();

        return view('attendance.leave-review', compact('upload', 'staffGaps'));
    }

    public function leaveReview(Request $request, BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);

        $data = $request->validate([
            'paid_leave'                    => ['nullable', 'array'],
            'paid_leave.*.checked'          => ['required', 'in:0,1'],
            'paid_leave.*.employee_id'      => ['required', 'exists:employees,id'],
            'paid_leave.*.date'             => ['required', 'date'],
            'paid_leave.*.leave_type_id'    => ['nullable', 'exists:leave_types,id'],
        ]);

        foreach ($data['paid_leave'] ?? [] as $row) {
            if ($row['checked'] !== '1') {
                continue; // Left unchecked — leave this date untouched, exactly as today.
            }

            $employee = Employee::find($row['employee_id']);
            if (! $employee || (int) $employee->branch_id !== (int) $upload->branch_id) {
                continue; // Not part of this upload's branch — ignore a tampered/stale row.
            }

            Attendance::updateOrCreate(
                ['employee_id' => $row['employee_id'], 'date' => $row['date']],
                [
                    'shift_id' => $employee->shift_id,
                    'status' => 'paid_leave',
                    'leave_type_id' => $row['leave_type_id'] ?? null,
                    'in_time' => null, 'out_time' => null, 'work_minutes' => 0,
                    'is_manual_entry' => true,
                    'source' => 'manual',
                ]
            );
        }

        return redirect()->route('attendance.upload.summary', $upload)->with('success', 'Paid leave confirmations saved.');
    }

    public function downloadUploadErrors(BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        abort_unless($upload->error_file_path && Storage::exists($upload->error_file_path), 404);
        return Storage::download($upload->error_file_path, 'upload-' . $upload->id . '-errors.csv');
    }

    // ── 11.3 Attendance Computation ───────────────────────────────────────
    // There is no separate "Process Attendance" screen/step anymore —
    // biometric-sourced attendance is computed automatically, right after
    // Confirm & Save on the upload screen, for exactly the employees/dates
    // found in that file. computeAttendanceForEmployees() below is the same
    // per-employee/per-date computation the old manual Process Attendance
    // screen used to run on demand.

    /**
     * Computes daily Attendance rows (in/out time, work hours, status,
     * late/OT) for the given employees across the given period, from
     * whichever AttendanceLog rows already exist for them. Always
     * recalculates (biometric upload is the only caller now, and a fresh
     * upload's own newly-imported logs should always win over whatever was
     * there before for that date).
     *
     * @return array{processed:int, skipped:int}
     */
    private function computeAttendanceForEmployees(\Illuminate\Support\Collection $employees, \Carbon\CarbonPeriod $period): array
    {
        $processed = $skipped = 0;

        foreach ($employees as $employee) {
            $branch = $employee->branch;

            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                // FSD 11.3 — "shall not be processed" guards.
                if ($employee->date_of_joining && $date->lt($employee->date_of_joining)) { $skipped++; continue; }
                $exit = $employee->exitRecord;
                if ($exit && $exit->last_working_date && $date->gt($exit->last_working_date)) { $skipped++; continue; }

                $existing = Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->first();
                $oldValues = $existing ? $existing->toArray() : null;

                $holidayStatus = $branch ? $this->resolveHolidayOrWeeklyOffStatus($employee, $branch, $date) : null;

                $attendance = $existing ?: new Attendance(['employee_id' => $employee->id, 'date' => $dateStr]);
                $attendance->shift_id = $attendance->shift_id ?: $employee->shift_id;

                if ($holidayStatus) {
                    $attendance->fill([
                        'status' => $holidayStatus, 'in_time' => null, 'out_time' => null,
                        'work_minutes' => 0, 'is_manual_entry' => false, 'source' => 'biometric',
                    ]);
                    $attendance->save();
                } else {
                    $logs = AttendanceLog::where('employee_id', $employee->id)
                        ->whereDate('punch_time', $dateStr)
                        ->where('is_processed', false)
                        ->orderBy('punch_time')->get();

                    if ($logs->isEmpty()) {
                        // No biometric punch at all for this employee on this
                        // date within the uploaded file's own period — mark
                        // Absent, UNLESS an approved leave already covers this
                        // date (LeaveController::approve() already stamped
                        // Attendance to reflect it — that override must not be
                        // clobbered by a biometric file uploaded afterward).
                        // The admin can still apply a Leave later for a date
                        // this uploads marks Absent; that later approval will
                        // override it the normal way.
                        $hasApprovedLeave = LeaveRequest::where('employee_id', $employee->id)
                            ->where('status', 'approved')
                            ->where('start_date', '<=', $dateStr)->where('end_date', '>=', $dateStr)
                            ->exists();
                        if ($hasApprovedLeave) {
                            if (! $existing) { $skipped++; }
                            continue;
                        }

                        $attendance->fill([
                            'status' => 'absent', 'in_time' => null, 'out_time' => null,
                            'work_minutes' => 0, 'is_manual_entry' => false, 'source' => 'biometric',
                        ]);
                        $attendance->save();
                        $this->applyDailyLopEligibility($attendance->fresh(), $employee);

                        if ($existing) {
                            AuditLog::write(auth()->id(), 'recalculate', 'attendance', $attendance->id, $oldValues, $attendance->fresh()->toArray(), $employee->branch_id, 'Marked Absent — no biometric punch found for this date');
                        }

                        $processed++;
                        continue;
                    }

                    $attendance->in_time  = $logs->first()->punch_time;
                    $attendance->out_time = $logs->count() > 1 ? $logs->last()->punch_time : null;

                    // Biometric Bulk Upload FSD point 5 — "if punch-out
                    // record is missing, apply the default shift end time."
                    // Only for a genuinely past day (today's shift may
                    // simply not be over yet) and only when a shift is
                    // actually assigned to fall back to.
                    $shiftForFallback = $attendance->shift_id ? Shift::find($attendance->shift_id) : null;
                    if (! $attendance->out_time && $shiftForFallback && $date->lt(now()->startOfDay())) {
                        $attendance->out_time = $date->copy()->setTimeFromTimeString($shiftForFallback->end_time);
                    }

                    $attendance->is_manual_entry = false;
                    $attendance->source = 'biometric';
                    $attendance->biometric_upload_id = $logs->first()->biometric_upload_id;
                    // Baseline before recalculate()'s rule-gated adjustment:
                    // recalculate() only OVERRIDES status when an
                    // AttendanceRule resolves for this employee/date — with
                    // no rule configured it leaves status untouched, so a
                    // baseline must be set here.
                    $attendance->status = $attendance->out_time ? 'present' : 'missing_punch';
                    $attendance->save();

                    $this->recalculate($attendance);
                    $logs->each(fn($l) => $l->update(['is_processed' => true, 'attendance_id' => $attendance->id]));
                }

                $this->applyDailyLopEligibility($attendance->fresh(), $employee);

                if ($existing) {
                    AuditLog::write(auth()->id(), 'recalculate', 'attendance', $attendance->id, $oldValues, $attendance->fresh()->toArray(), $employee->branch_id, 'Auto-computed from biometric upload');
                }

                $processed++;
            }
        }

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /** FSD 11.3 — "Apply holiday and weekly off rules" (employee-specific or branch rule). */
    private function resolveHolidayOrWeeklyOffStatus(Employee $employee, Branch $branch, \Carbon\Carbon $date): ?string
    {
        $holiday = Holiday::where('is_active', true)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->get()
            ->first(fn($h) => $h->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type));

        if ($holiday) {
            return $holiday->is_paid ? 'paid_holiday' : 'unpaid_holiday';
        }

        $dayNameToIndex = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $offIndexes = $branch->weekly_off_days
            ? array_values(array_filter(array_map(fn($d) => $dayNameToIndex[$d] ?? null, $branch->weekly_off_days), fn($v) => $v !== null))
            : [0, 6];

        return in_array($date->dayOfWeek, $offIndexes, true) ? 'weekly_off' : null;
    }

    /**
     * FSD 11.3 "Derive ... LOP eligibility" — informational per-day value
     * for Register display (FSD 11.4). Purely additive: Module 8's own
     * monthly payroll LOP calculation is untouched and keeps independently
     * deriving its totals from Attendance/Leave exactly as before.
     */
    private function applyDailyLopEligibility(Attendance $attendance, Employee $employee): void
    {
        $rule = BusinessRule::resolveForEmployee(
            $employee, 'lop', $employee->branch_id, $employee->primary_employee_type,
            $employee->labour_type, $employee->contractor_id, $attendance->date->toDateString()
        );
        $detail = $rule?->lopRule;
        if (! $detail) {
            return;
        }

        $lopDays = match (true) {
            in_array($attendance->status, ['absent', 'missing_punch'], true) => $detail->absent_day_as_lop || $attendance->status === 'missing_punch' ? (float) $detail->full_day_lop_value : 0.0,
            $attendance->status === 'half_day' => (float) $detail->half_day_lop_value,
            $attendance->status === 'unpaid_leave' && $detail->unpaid_leave_as_lop => 1.0,
            default => 0.0,
        };

        if ($lopDays > 0) {
            $attendance->update(['lop_days' => $lopDays]);
        }
    }

    // ── 11.4 Register actions: recalculate selected, exports ─────────────

    public function recalculateSelected(Request $request)
    {
        $request->validate(['attendance_ids' => ['required', 'array'], 'attendance_ids.*' => ['exists:attendance,id']]);

        // Module 11 (FSD 15.2) — dedicated Recalculate permission (previously
        // reused the general Process flag).
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'recalculate')) {
            abort(403, 'You do not have the "Recalculate" permission for Attendance in Branch Administration.');
        }

        $count = 0;
        foreach (Attendance::whereIn('id', $request->attendance_ids)->get() as $attendance) {
            BranchScope::assertBranchAccess($attendance->employee?->branch_id);
            $oldValues = $attendance->toArray();
            $this->recalculate($attendance);
            AuditLog::write(auth()->id(), 'recalculate', 'attendance', $attendance->id, $oldValues, $attendance->fresh()->toArray(), $attendance->employee?->branch_id);
            $count++;
        }

        return back()->with('success', "Recalculated {$count} record(s).");
    }

    public function approveOvertime(Request $request, Attendance $attendance)
    {
        BranchScope::assertBranchAccess($attendance->employee?->branch_id);

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'approve')) {
            abort(403, 'You do not have the "Approve" permission for Attendance in Branch Administration.');
        }

        $request->validate(['action' => ['required', 'in:approve,reject']]);

        $attendance->update([
            'ot_approval_status' => $request->action === 'approve' ? 'approved' : 'rejected',
            'ot_approved_by' => auth()->id(),
            'ot_approved_at' => now(),
        ]);

        return back()->with('success', 'Overtime ' . $request->action . 'd.');
    }

    public function export(Request $request)
    {
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'export_excel')) {
            abort(403, 'You do not have the "Export Excel" permission for Attendance in Branch Administration.');
        }

        $records = $this->registerQuery($request)->get();

        return $this->streamCsv('attendance-register-' . now()->format('Y-m-d') . '.csv',
            ['Date', 'Employee Number', 'Employee', 'Shift', 'In Time', 'Out Time', 'Total Hours', 'Late Minutes', 'Early Exit Minutes', 'OT Hours', 'Status', 'Leave Type', 'LOP Days', 'Source', 'Remarks'],
            $records->map(fn($a) => [
                $a->date->format('d-m-Y'), $a->employee->employee_code ?? '', $a->employee->full_name ?? '',
                $a->shift->name ?? '', optional($a->in_time)->format('H:i'), optional($a->out_time)->format('H:i'),
                $a->work_hours, $a->late_minutes, $a->early_exit_minutes, $a->ot_hours,
                $a->status_label, $a->leaveType->name ?? '', $a->lop_days ?? '', $a->source_label, $a->remarks,
            ])->all()
        );
    }

    public function exportPdf(Request $request)
    {
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'export_pdf')) {
            abort(403, 'You do not have the "Export PDF" permission for Attendance in Branch Administration.');
        }

        $records = $this->registerQuery($request)->limit(1000)->get();
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to   = $request->input('to_date', now()->toDateString());

        $pdf = Pdf::loadView('attendance.export-pdf', compact('records', 'from', 'to'))->setPaper('a4', 'landscape');
        return $pdf->download('attendance-register-' . now()->format('Y-m-d') . '.pdf');
    }

    private function registerQuery(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to   = $request->input('to_date', now()->toDateString());

        $query = Attendance::with(['employee.department', 'shift', 'leaveType'])
            ->whereBetween('date', [$from, $to])->orderBy('date');
        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('department_id')) { $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id)); }
        if ($request->filled('shift_id')) { $query->where('shift_id', $request->shift_id); }

        return $query;
    }

    private function streamCsv(string $filename, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) { fputcsv($handle, $row); }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── 11.5 Attendance Correction ────────────────────────────────────────

    public function correctionForm(Request $request)
    {
        $employee = null;
        $attendance = null;

        if ($request->filled('employee_id') && $request->filled('date')) {
            $employee = Employee::find($request->employee_id);
            BranchScope::assertBranchAccess($employee?->branch_id);
            $attendance = Attendance::where('employee_id', $request->employee_id)->where('date', $request->date)->first();
        }

        $employees = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $leaveTypes = $employee
            ? LeaveType::where('is_active', true)->where('branch_id', $employee->branch_id)->get()
            : BranchScope::scopeQuery(LeaveType::where('is_active', true))->get();
        $canSetOvertime = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'attendance', 'approve');

        return view('attendance.correction', compact('employees', 'employee', 'attendance', 'leaveTypes', 'canSetOvertime'));
    }

    public function correction(Request $request)
    {
        $data = $request->validate([
            'employee_id'          => ['required', 'exists:employees,id'],
            'attendance_date'      => ['required', 'date'],
            'corrected_in_time'    => ['nullable', 'date_format:H:i'],
            'corrected_out_time'   => ['nullable', 'date_format:H:i'],
            'status'               => ['required', 'string'],
            'leave_type_id'        => ['nullable', 'required_if:status,paid_leave,unpaid_leave,on_leave', 'exists:leave_types,id'],
            'ot_hours'             => ['nullable', 'numeric', 'min:0'],
            'correction_reason'    => ['required', 'string', 'max:1000'],
            'supporting_document'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        BranchScope::assertBranchAccess($employee->branch_id);

        if (! empty($data['leave_type_id'])) {
            $leaveType = LeaveType::findOrFail($data['leave_type_id']);
            if ((int) $leaveType->branch_id !== (int) $employee->branch_id) {
                return back()->withErrors(['leave_type_id' => 'This leave type does not belong to the employee\'s branch.'])->withInput();
            }
        }

        $attendance = Attendance::firstOrNew(['employee_id' => $data['employee_id'], 'date' => $data['attendance_date']]);
        $oldValues = $attendance->exists ? $attendance->toArray() : null;
        $wasBiometricOrWeb = $attendance->exists && in_array($attendance->source, ['biometric', 'web', 'mobile'], true);

        // FSD 11.5 — cross-day aware: an earlier out clock-time only rolls
        // to the next calendar day when the employee's own shift is
        // actually configured to cross midnight (end_time < start_time).
        // Without this check, any same-day data-entry mistake (out
        // accidentally earlier than in) would silently "fix itself" into a
        // valid next-day time instead of being rejected.
        $shift = ($attendance->shift_id ?: $employee->shift_id) ? Shift::find($attendance->shift_id ?: $employee->shift_id) : null;
        $shiftCrossesMidnight = $shift && $shift->end_time < $shift->start_time;

        $inTime = $data['corrected_in_time'] ? $data['attendance_date'] . ' ' . $data['corrected_in_time'] . ':00' : null;
        $outTime = null;
        if ($data['corrected_out_time']) {
            $outDate = $data['attendance_date'];
            if ($inTime && $shiftCrossesMidnight && $data['corrected_out_time'] < $data['corrected_in_time']) {
                $outDate = \Carbon\Carbon::parse($data['attendance_date'])->addDay()->toDateString();
            }
            $outTime = $outDate . ' ' . $data['corrected_out_time'] . ':00';
        }

        if ($inTime && $outTime && $outTime <= $inTime) {
            return back()->withErrors(['corrected_out_time' => 'Corrected Out Time must be after Corrected In Time.'])->withInput();
        }

        $canSetOvertime = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'attendance', 'approve');

        $attendance->fill([
            'employee_id' => $data['employee_id'],
            'date' => $data['attendance_date'],
            'shift_id' => $attendance->shift_id ?: $employee->shift_id,
            'in_time' => $inTime ?: $attendance->in_time,
            'out_time' => $outTime ?: $attendance->out_time,
            'status' => $data['status'],
            'leave_type_id' => $data['leave_type_id'] ?? null,
            'correction_reason' => $data['correction_reason'],
            'is_manual_entry' => true,
            'source' => $wasBiometricOrWeb ? 'corrected' : 'manual',
        ]);

        if ($canSetOvertime && $request->filled('ot_hours')) {
            $attendance->ot_minutes = (int) round((float) $data['ot_hours'] * 60);
        }

        if ($inTime && $outTime) {
            // Real timestamps (not time-of-day minutes) so a cross-day
            // corrected out-time is measured correctly.
            // diffInMinutes() returns a signed value in this Carbon version
            // (negative when called on the later timestamp against the
            // earlier one) — call it on the earlier ($inTime) timestamp so
            // the result is always the correct positive duration.
            $attendance->work_minutes = max(0, \Carbon\Carbon::parse($inTime)->diffInMinutes(\Carbon\Carbon::parse($outTime)));
        }

        if ($request->hasFile('supporting_document')) {
            $attendance->supporting_document_path = $request->file('supporting_document')->store('attendance-corrections/' . $data['employee_id'], 'public');
        }

        $attendance->save();

        AuditLog::write(auth()->id(), 'correction', 'attendance', $attendance->id, $oldValues, $attendance->fresh()->toArray(), $employee->branch_id, $data['correction_reason']);

        // FSD 11.5 — "Attendance correction after payroll processing shall
        // generate a warning" / "Payroll-impacting corrections shall
        // require payroll recalculation or adjustment." A warning only —
        // this never calls into PayrollController or alters any payroll
        // record, avoiding surprising side effects on unrelated fields.
        $payrollExists = \App\Models\PayrollRecord::where('employee_id', $data['employee_id'])
            ->where('month', \Carbon\Carbon::parse($data['attendance_date'])->month)
            ->where('year', \Carbon\Carbon::parse($data['attendance_date'])->year)
            ->exists();

        $message = 'Attendance correction saved.';
        if ($payrollExists) {
            $message .= ' Payroll for this period already exists and may need to be regenerated or adjusted to reflect this correction.';
        }

        return redirect()->route('attendance.index', ['from_date' => $data['attendance_date'], 'to_date' => $data['attendance_date']])
            ->with($payrollExists ? 'warning' : 'success', $message);
    }

    /**
     * Department Work Assignment — records which department an employee
     * worked in on which date(s), snapshotting that department's Value Per
     * Day at entry time (Masters > Department). Feeds inter-department
     * daily-rate work into payroll generation.
     */
    public function departmentWorkForm(Request $request)
    {
        $employees = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $departments = BranchScope::scopeQuery(Department::where('is_active', true)->orderBy('name'))->get();

        $recent = collect();
        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                BranchScope::assertBranchAccess($employee->branch_id);
                $recent = DepartmentWorkAssignment::with('department')
                    ->where('employee_id', $employee->id)
                    ->orderByDesc('work_date')
                    ->limit(20)
                    ->get();
            }
        }

        return view('attendance.department-work', compact('employees', 'departments', 'recent'));
    }

    public function departmentWork(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'dates'         => ['required', 'array', 'min:1'],
            'dates.*'       => ['date'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        BranchScope::assertBranchAccess($employee->branch_id);

        $department = Department::findOrFail($data['department_id']);
        BranchScope::assertBranchAccess($department->branch_id);

        if ($department->value_per_day === null) {
            return back()->withErrors(['department_id' => 'This department has no Value Per Day configured yet — set one in Masters > Departments first.'])->withInput();
        }

        foreach (array_unique($data['dates']) as $date) {
            DepartmentWorkAssignment::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $date],
                ['department_id' => $department->id, 'value_per_day' => $department->value_per_day, 'created_by' => auth()->id()]
            );
        }

        return redirect()->route('attendance.department-work.form', ['employee_id' => $employee->id])
            ->with('success', 'Department work assignment saved for ' . count($data['dates']) . ' date(s).');
    }

    /**
     * Module 4 FSD 8.4 — Attendance Rule. This is called for every
     * mark()/manual()/process() save exactly as before this feature existed;
     * the only behavior change is additive (grace/rounding/late/OT/full-half-day
     * computation) and only when an Attendance Rule actually resolves for
     * the employee's context — no rule configured means this method behaves
     * byte-for-byte as it did previously (work_minutes = out - in, nothing
     * else touched).
     *
     * Guardrail (FSD: "shall not silently replace manually corrected
     * attendance"): entries submitted through the dedicated manual()/
     * correction() flow are flagged is_manual_entry=true and are NEVER
     * touched here — only mark()'s/process()'s routine/bulk entries
     * (is_manual_entry stays false) are auto-computed/corrected by the rule
     * engine, which is the intended automation target, not a "correction."
     */
    private function recalculate(Attendance $attendance): void
    {
        if (! $attendance->in_time || ! $attendance->out_time) {
            $this->applyIncompletePunchTreatment($attendance);
            return;
        }

        $this->computeFromPunches($attendance);
    }

    /** Extracted from recalculate() so process()'s bulk path reuses the exact same calculation, unduplicated. */
    private function computeFromPunches(Attendance $attendance): void
    {
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
                    // Biometric Bulk Upload FSD point 4 — "check if OT is
                    // applicable" before recording it; an employee with
                    // is_ot_applicable = false never accrues OT minutes,
                    // regardless of how late they punched out.
                    $updates['ot_minutes'] = $employee?->is_ot_applicable ? max(0, $outMin - $shiftEndMin) : 0;
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
     * for manually-corrected entries. FSD 11.3 extension: when a configured
     * treatment doesn't resolve to absent/half_day, the record now always
     * gets a meaningful status (`missing_punch` for a single punch,
     * `pending_review` for zero) instead of being silently left untouched —
     * this only changes behavior when a rule IS configured (no rule
     * configured is still a complete no-op, exactly as before).
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
            return; // no rule configured — untouched, exactly as before this feature
        }

        $hasOnePunch = (bool) ($attendance->in_time xor $attendance->out_time);
        $treatment = $hasOnePunch ? $detail->single_punch_treatment : $detail->missing_punch_treatment;

        $status = match (true) {
            in_array($treatment, ['absent', 'half_day'], true) => $treatment,
            default => $hasOnePunch ? 'missing_punch' : 'pending_review',
        };

        $attendance->update([
            'status' => $status,
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
