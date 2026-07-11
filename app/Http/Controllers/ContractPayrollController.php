<?php
/**
 * File: app/Http/Controllers/ContractPayrollController.php
 * Purpose: Calculate, view, and track payment of wages for contract workers based on attendance.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\ContractWorkerAttendance;
use App\Models\ContractWorkerPayroll;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractPayrollController extends Controller
{
    public function index(Request $request)
    {
        $contractors = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get(['id', 'name', 'code']);

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $query = BranchScope::scopeQueryVia(
            ContractWorkerPayroll::with(['worker', 'contractor']), 'contractor'
        )
            ->when($request->filled('contractor_id'), fn($q) => $q->where('contractor_id', $request->contractor_id))
            ->where('month', $month)->where('year', $year)
            ->when($request->filled('status'), fn($q) => $q->where('payment_status', $request->status))
            ->orderBy('contractor_id')->orderBy('created_at', 'desc');

        $records = $query->paginate(25)->withQueryString();

        $summary = BranchScope::scopeQueryVia(
            ContractWorkerPayroll::where('month', $month)->where('year', $year), 'contractor'
        )
            ->when($request->filled('contractor_id'), fn($q) => $q->where('contractor_id', $request->contractor_id))
            ->selectRaw('COUNT(*) as count, SUM(gross_wages) as gross, SUM(net_wages) as net')
            ->first();

        return view('contract-payroll.index', compact('contractors', 'records', 'summary', 'month', 'year'));
    }

    public function calculateForm()
    {
        $contractors = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get(['id', 'name', 'code']);
        return view('contract-payroll.calculate', compact('contractors'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
            'month'         => ['required', 'integer', 'between:1,12'],
            'year'          => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $contractor = Contractor::findOrFail($request->contractor_id);
        BranchScope::assertBranchAccess($contractor->branch_id);

        $month      = (int) $request->month;
        $year       = (int) $request->year;

        $workers = $contractor->contractWorkers()->active()->get();

        if ($workers->isEmpty()) {
            return back()->with('error', 'No active contract workers found for this contractor.');
        }

        $totalDays = $this->countWorkingDays($month, $year);
        $generated = 0;

        DB::transaction(function () use ($workers, $month, $year, $totalDays, $contractor, &$generated) {
            foreach ($workers as $worker) {
                $rows = ContractWorkerAttendance::where('contract_worker_id', $worker->id)
                    ->whereMonth('date', $month)->whereYear('date', $year)->get();

                $presentDays = $rows->where('status', 'present')->count();
                $halfDays    = $rows->where('status', 'half_day')->count();
                $absentDays  = $rows->where('status', 'absent')->count();
                $otHours     = (float) $rows->sum('ot_hours');

                $effectiveDays = $presentDays + ($halfDays * 0.5);

                if ($worker->wage_type === 'daily') {
                    $totalWages = round($effectiveDays * $worker->wage_amount, 2);
                    // OT at 1.5× hourly equivalent (daily_rate / 8 hours)
                    $hourlyRate = ($worker->wage_amount > 0) ? $worker->wage_amount / 8 : 0;
                    $otAmount   = round($otHours * $hourlyRate * 1.5, 2);
                } else {
                    // Monthly: pro-rate by attendance; no separate OT calculation
                    $totalWages = ($totalDays > 0)
                        ? round(($effectiveDays / $totalDays) * $worker->wage_amount, 2)
                        : $worker->wage_amount;
                    $otAmount = 0;
                }

                $grossWages = $totalWages + $otAmount;

                ContractWorkerPayroll::updateOrCreate(
                    ['contract_worker_id' => $worker->id, 'month' => $month, 'year' => $year],
                    [
                        'contractor_id'  => $contractor->id,
                        'total_days'     => $totalDays,
                        'present_days'   => $presentDays,
                        'absent_days'    => $absentDays,
                        'half_days'      => $halfDays,
                        'ot_hours'       => $otHours,
                        'wage_type'      => $worker->wage_type,
                        'wage_amount'    => $worker->wage_amount,
                        'total_wages'    => $totalWages,
                        'ot_amount'      => $otAmount,
                        'gross_wages'    => $grossWages,
                        'deductions'     => 0,
                        'net_wages'      => $grossWages,
                        'payment_status' => 'pending',
                        'generated_by'   => auth()->id(),
                    ]
                );
                $generated++;
            }
        });

        return redirect()->route('contract-payroll.index', ['contractor_id' => $contractor->id, 'month' => $month, 'year' => $year])
            ->with('success', "Payroll generated for {$generated} worker(s) under {$contractor->name}.");
    }

    public function show(int $id)
    {
        $record = ContractWorkerPayroll::with(['worker', 'contractor', 'generator'])->findOrFail($id);
        BranchScope::assertBranchAccess($record->contractor?->branch_id);
        return view('contract-payroll.show', compact('record'));
    }

    public function markPaid(Request $request, int $id)
    {
        $record = ContractWorkerPayroll::with('contractor')->findOrFail($id);
        BranchScope::assertBranchAccess($record->contractor?->branch_id);

        $request->validate([
            'payment_date'    => ['required', 'date'],
            'payment_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $record->update([
            'payment_status'  => 'paid',
            'payment_date'    => $request->payment_date,
            'payment_remarks' => $request->payment_remarks,
        ]);

        return back()->with('success', 'Payment recorded successfully.');
    }

    private function countWorkingDays(int $month, int $year): int
    {
        $current = Carbon::create($year, $month, 1)->startOfDay();
        $last    = $current->copy()->endOfMonth();
        $days    = 0;

        while ($current <= $last) {
            if (!$current->isSunday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }
}
