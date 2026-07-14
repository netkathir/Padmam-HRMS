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
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Setting;
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
    public function index(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to   = $request->input('to_date', now()->toDateString());

        $query = Attendance::with(['employee.department', 'employee.employeeType', 'employee.contractor', 'shift', 'leaveType'])
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')->orderBy('in_time');

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

        $attendance  = $query->paginate(25)->withQueryString();
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        $employeeTypes = EmployeeType::where('is_active', true)->get();
        $contractors = BranchScope::scopeQueryIncludingGlobal(Contractor::where('is_active', true))->orderBy('name')->get();
        $shifts = Shift::where('is_active', true)->get();
        $currentBranchId = BranchScope::currentBranchId();
        $branches = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();

        $summary = BranchScope::scopeQueryVia(Attendance::whereBetween('date', [$from, $to]), 'employee')
            ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('attendance.index', compact(
            'attendance', 'departments', 'summary', 'from', 'to', 'employeeTypes',
            'contractors', 'shifts', 'branches', 'currentBranchId'
        ));
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

    // ── 11.2 Biometric Excel Upload ───────────────────────────────────────

    public function uploadForm()
    {
        $currentBranchId = BranchScope::currentBranchId();
        $branches = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();
        return view('attendance.upload', compact('branches', 'currentBranchId'));
    }

    public function upload(Request $request, BiometricUploadService $service)
    {
        $data = $request->validate([
            'branch_id'   => ['required', 'exists:branches,id'],
            'period_from' => ['required', 'date'],
            'period_to'   => ['required', 'date', 'after_or_equal:period_from'],
            'file'        => ['required', 'file', 'mimes:xls,xlsx'],
            'remarks'     => ['nullable', 'string', 'max:255'],
        ]);

        BranchScope::assertBranchAccess($data['branch_id']);

        $path = $request->file('file')->store('biometric-uploads');

        $upload = BiometricUpload::create([
            'branch_id'         => $data['branch_id'],
            'period_from'       => $data['period_from'],
            'period_to'         => $data['period_to'],
            'file_path'         => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'remarks'           => $data['remarks'] ?? null,
            'uploaded_by'       => auth()->id(),
            'status'            => 'mapping',
        ]);

        // FSD 11.2 — "Users shall be warned before re-uploading data for an
        // already processed period." Non-blocking — upload still proceeds.
        $alreadyProcessed = Attendance::whereHas('employee', fn($q) => $q->where('branch_id', $data['branch_id']))
            ->whereBetween('date', [$data['period_from'], $data['period_to']])
            ->exists();

        return redirect()->route('attendance.upload.mapping', $upload)
            ->with($alreadyProcessed ? 'warning' : 'success', $alreadyProcessed
                ? 'Attendance already exists for part of this period — re-uploading may create duplicate punches for review.'
                : 'File uploaded. Confirm the sheet and column mapping below.');
    }

    public function mappingForm(Request $request, BiometricUpload $upload, BiometricUploadService $service)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        $absolutePath = Storage::path($upload->file_path);

        $sheetNames = $service->sheetNames($absolutePath);
        $selectedSheet = $request->input('sheet_name', $upload->sheet_name ?: ($sheetNames[0] ?? null));

        $headerRow = $selectedSheet ? $service->headerRow($absolutePath, $selectedSheet) : [];
        $defaultLabels = json_decode(Setting::get('attendance', 'default_excel_column_mapping', '{}'), true) ?: [];
        $guessedMapping = $service->guessMapping($headerRow, $defaultLabels);

        return view('attendance.upload-mapping', compact('upload', 'sheetNames', 'selectedSheet', 'headerRow', 'guessedMapping'));
    }

    public function confirmMapping(Request $request, BiometricUpload $upload, BiometricUploadService $service)
    {
        BranchScope::assertBranchAccess($upload->branch_id);

        $data = $request->validate([
            'sheet_name' => ['required', 'string'],
            'mapping'    => ['required', 'array'],
            'mapping.employee_number' => ['nullable', 'string'],
            'mapping.biometric_id'    => ['nullable', 'string'],
            'mapping.punch_date'      => ['required', 'string'],
            'mapping.punch_time'      => ['required', 'string'],
        ]);

        if (empty($data['mapping']['employee_number']) && empty($data['mapping']['biometric_id'])) {
            return back()->withErrors(['mapping' => 'Map at least one of Employee Number or Biometric ID.'])->withInput();
        }

        $upload->update(['sheet_name' => $data['sheet_name'], 'column_mapping' => $data['mapping'], 'status' => 'processing']);

        [$counts, $errors] = $service->validateAndImport($upload, $data['mapping']);
        $errorFilePath = $service->generateErrorFile($upload, $errors);

        $upload->update(array_merge($counts, ['error_file_path' => $errorFilePath, 'status' => 'completed']));

        return redirect()->route('attendance.upload.summary', $upload)->with('success', 'Upload processed.');
    }

    public function uploadSummary(BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        return view('attendance.upload-summary', compact('upload'));
    }

    public function downloadUploadErrors(BiometricUpload $upload)
    {
        BranchScope::assertBranchAccess($upload->branch_id);
        abort_unless($upload->error_file_path && Storage::exists($upload->error_file_path), 404);
        return Storage::download($upload->error_file_path, 'upload-' . $upload->id . '-errors.csv');
    }

    // ── 11.3 Attendance Processing ────────────────────────────────────────

    public function processForm()
    {
        $currentBranchId = BranchScope::currentBranchId();
        $branches = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();
        $employeeTypes = EmployeeType::where('is_active', true)->get();
        $contractors = BranchScope::scopeQueryIncludingGlobal(Contractor::where('is_active', true))->orderBy('name')->get();
        $shifts = Shift::where('is_active', true)->get();
        $canRecalculate = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'attendance', 'process');

        return view('attendance.process', compact('branches', 'currentBranchId', 'employeeTypes', 'contractors', 'shifts', 'canRecalculate'));
    }

    public function process(Request $request)
    {
        $data = $request->validate([
            'branch_id'             => ['required', 'exists:branches,id'],
            'period_from'           => ['required', 'date'],
            'period_to'             => ['required', 'date', 'after_or_equal:period_from'],
            'employee_type_id'      => ['nullable', 'exists:employee_types,id'],
            'labour_type'           => ['nullable', 'in:company_labour,contract_labour'],
            'contractor_id'         => ['nullable', 'exists:contractors,id'],
            'shift_id'              => ['nullable', 'exists:shifts,id'],
            'recalculate_existing'  => ['boolean'],
            'remarks'               => ['nullable', 'string', 'max:255'],
        ]);

        BranchScope::assertBranchAccess($data['branch_id']);
        $recalculate = $request->boolean('recalculate_existing');

        // FSD 11.3 — "Attendance recalculation requires permission."
        if ($recalculate && BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'process')) {
            abort(403, 'You do not have the "Process" permission for Attendance in Branch Administration.');
        }

        $branch = Branch::find($data['branch_id']);
        $employees = Employee::where('branch_id', $data['branch_id'])->where('status', 'active')
            ->when($data['employee_type_id'] ?? null, fn($q, $v) => $q->where('employee_type_id', $v))
            ->when($data['labour_type'] ?? null, fn($q, $v) => $q->where('labour_type', $v))
            ->when($data['contractor_id'] ?? null, fn($q, $v) => $q->where('contractor_id', $v))
            ->when($data['shift_id'] ?? null, fn($q, $v) => $q->where('shift_id', $v))
            ->get();

        $processed = $skipped = 0;
        $period = \Carbon\CarbonPeriod::create($data['period_from'], $data['period_to']);

        foreach ($employees as $employee) {
            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                // FSD 11.3 — "shall not be processed" guards.
                if ($employee->date_of_joining && $date->lt($employee->date_of_joining)) { $skipped++; continue; }
                $exit = $employee->exitRecord;
                if ($exit && $exit->last_working_date && $date->gt($exit->last_working_date)) { $skipped++; continue; }

                $existing = Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->first();
                if ($existing && ! $recalculate) { $skipped++; continue; }

                $oldValues = $existing ? $existing->toArray() : null;

                $holidayStatus = $this->resolveHolidayOrWeeklyOffStatus($employee, $branch, $date);

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
                        // FSD 11.3 — "shall not be processed without valid
                        // biometric data unless manual attendance entry is
                        // permitted" — no punches, nothing to compute; the
                        // existing manual()/mark() screens remain available.
                        if (! $existing) { $skipped++; }
                        continue;
                    }

                    $attendance->in_time  = $logs->first()->punch_time;
                    $attendance->out_time = $logs->count() > 1 ? $logs->last()->punch_time : null;
                    $attendance->is_manual_entry = false;
                    $attendance->source = 'biometric';
                    $attendance->biometric_upload_id = $logs->first()->biometric_upload_id;
                    // Baseline before recalculate()'s rule-gated adjustment:
                    // recalculate() only OVERRIDES status when an
                    // AttendanceRule resolves for this employee/date — with
                    // no rule configured it leaves status untouched, so a
                    // baseline must be set here (unlike mark()/manual(),
                    // process() has no human-entered status to start from).
                    $attendance->status = $attendance->out_time ? 'present' : 'missing_punch';
                    $attendance->save();

                    $this->recalculate($attendance);
                    $logs->each(fn($l) => $l->update(['is_processed' => true, 'attendance_id' => $attendance->id]));
                }

                $this->applyDailyLopEligibility($attendance->fresh(), $employee);

                if ($existing && $recalculate) {
                    AuditLog::write(auth()->id(), 'recalculate', 'attendance', $attendance->id, $oldValues, $attendance->fresh()->toArray(), $employee->branch_id, $data['remarks'] ?? null);
                }

                $processed++;
            }
        }

        return redirect()->route('attendance.index', ['from_date' => $data['period_from'], 'to_date' => $data['period_to']])
            ->with('success', "Attendance processed: {$processed} day(s) computed, {$skipped} skipped.");
    }

    /** FSD 11.3 — "Apply holiday and weekly off rules" (employee-specific or branch rule). */
    private function resolveHolidayOrWeeklyOffStatus(Employee $employee, Branch $branch, \Carbon\Carbon $date): ?string
    {
        $holiday = Holiday::where('is_active', true)->where('date', $date->toDateString())
            ->where(fn($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
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

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'attendance', 'process')) {
            abort(403, 'You do not have the "Process" permission for Attendance in Branch Administration.');
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
        $leaveTypes = LeaveType::where('is_active', true)->get();
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
