<?php
/**
 * File: app/Http/Controllers/ContractAttendanceController.php
 * Purpose: Mark and view daily attendance for employees assigned to contractors,
 *          with monthly report support. Uses the main attendance table.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Contractor;
use App\Models\Employee;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $contractor  = null;
        $attendance  = collect();
        $summary     = collect();
        $date        = $request->input('date', now()->toDateString());

        if ($request->filled('contractor_id')) {
            $contractor  = Contractor::findOrFail($request->contractor_id);
            $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');

            $query = Attendance::with(['employee.department', 'employee.designation'])
                ->whereIn('employee_id', $employeeIds)
                ->where('date', $date)
                ->orderBy('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $attendance = $query->paginate(25)->withQueryString();

            $summary = Attendance::whereIn('employee_id', $employeeIds)
                ->where('date', $date)
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');
        }

        return view('contract-attendance.index', compact('contractors', 'contractor', 'attendance', 'summary', 'date'));
    }

    public function markForm(Request $request)
    {
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $contractor  = null;
        $workers     = collect();
        $existingMap = collect();
        $date        = $request->input('date', now()->toDateString());

        if ($request->filled('contractor_id')) {
            $contractor = Contractor::findOrFail($request->contractor_id);

            $workers = BranchScope::scopeQuery($contractor->employees())
                ->where('status', 'active')
                ->with(['department', 'designation'])
                ->orderBy('first_name')
                ->get();

            if ($workers->isNotEmpty()) {
                $existingMap = Attendance::whereIn('employee_id', $workers->pluck('id'))
                    ->where('date', $date)
                    ->get()
                    ->keyBy('employee_id');
            }
        }

        return view('contract-attendance.mark', compact('contractors', 'contractor', 'workers', 'existingMap', 'date'));
    }

    public function mark(Request $request)
    {
        $request->validate([
            'contractor_id'         => ['required', 'exists:contractors,id'],
            'date'                  => ['required', 'date'],
            'attendance'            => ['required', 'array'],
            'attendance.*.status'   => ['required', 'in:present,absent,half_day'],
            'attendance.*.in_time'  => ['nullable', 'date_format:H:i'],
            'attendance.*.out_time' => ['nullable', 'date_format:H:i'],
            'attendance.*.ot_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $contractorId = $request->contractor_id;
        $date         = $request->date;

        $scopedBranchId = BranchScope::currentBranchId();

        DB::transaction(function () use ($request, $date, $scopedBranchId) {
            foreach ($request->attendance as $employeeId => $data) {
                if ($scopedBranchId !== null) {
                    $employee = Employee::find($employeeId);
                    if (! $employee || $employee->branch_id !== $scopedBranchId) continue;
                }

                $inTime  = !empty($data['in_time'])
                    ? ($date . ' ' . $data['in_time'] . ':00')
                    : null;
                $outTime = !empty($data['out_time'])
                    ? ($date . ' ' . $data['out_time'] . ':00')
                    : null;
                $otMins  = isset($data['ot_hours']) ? (int) round((float) $data['ot_hours'] * 60) : 0;

                Attendance::updateOrCreate(
                    ['employee_id' => $employeeId, 'date' => $date],
                    [
                        'status'          => $data['status'],
                        'in_time'         => $inTime,
                        'out_time'        => $outTime,
                        'ot_minutes'      => $otMins,
                        'source'          => 'manual',
                        'is_manual_entry' => true,
                        'remarks'         => $data['remarks'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('contract-attendance.index', ['contractor_id' => $contractorId, 'date' => $date])
            ->with('success', 'Attendance saved successfully.');
    }

    public function report(Request $request)
    {
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $month      = (int) $request->input('month', now()->month);
        $year       = (int) $request->input('year', now()->year);
        $contractor = null;
        $workerRows = collect();
        $workers    = collect();

        if ($request->filled('contractor_id')) {
            $contractor  = Contractor::findOrFail($request->contractor_id);
            $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');
            $workers     = BranchScope::scopeQuery($contractor->employees())
                ->with(['department', 'designation'])
                ->orderBy('first_name')
                ->get()
                ->keyBy('id');

            $workerRows = Attendance::whereIn('employee_id', $employeeIds)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->select('employee_id')
                ->selectRaw("COUNT(CASE WHEN status = 'present'  THEN 1 END) as present_count")
                ->selectRaw("COUNT(CASE WHEN status = 'absent'   THEN 1 END) as absent_count")
                ->selectRaw("COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_day_count")
                ->selectRaw('COALESCE(SUM(ot_minutes), 0) / 60 as total_ot_hours')
                ->groupBy('employee_id')
                ->get()
                ->keyBy('employee_id');
        }

        return view('contract-attendance.report', compact('contractors', 'contractor', 'workerRows', 'workers', 'month', 'year'));
    }
}
