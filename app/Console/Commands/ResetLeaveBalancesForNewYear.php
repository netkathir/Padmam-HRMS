<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Runs every 1 January at 00:00 (see bootstrap/app.php's withSchedule()).
 * Creates each active employee's opening LeaveBalance row for the new year,
 * one per active LeaveType applicable to that employee's classification.
 * No carry-forward: any unused balance from the previous year simply
 * lapses — the new row always starts at opening_balance 0, allocated_days
 * = LeaveType::days_per_year. Safe to re-run (firstOrCreate — never
 * overwrites a year's balance that already exists, e.g. from a manual run
 * or a previous partial run).
 */
class ResetLeaveBalancesForNewYear extends Command
{
    protected $signature = 'leave:reset-yearly-balances {--year= : Target year to create balances for (defaults to the current year)}';

    protected $description = 'Create the new year\'s opening leave balance for every active employee and applicable leave type (no carry-forward).';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);

        // LeaveType is per-branch (see LeaveTypeController) — grouped here
        // so each employee is only ever matched against their OWN branch's
        // leave types, never another branch's, even though every branch's
        // "Casual Leave" etc. now exists as a separate row with its own id.
        $leaveTypesByBranch = LeaveType::where('is_active', true)->get()->groupBy('branch_id');
        if ($leaveTypesByBranch->isEmpty()) {
            $this->warn('No active leave types found — nothing to do.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        Employee::where('status', 'active')
            ->select(['id', 'branch_id', 'primary_employee_type', 'labour_type'])
            ->chunkById(200, function ($employees) use ($leaveTypesByBranch, $year, &$created, &$skipped) {
                foreach ($employees as $employee) {
                    $leaveTypes = $leaveTypesByBranch->get($employee->branch_id, collect());
                    foreach ($leaveTypes as $leaveType) {
                        if (! $leaveType->appliesToEmployeeType($employee->primary_employee_type, $employee->labour_type)) {
                            continue;
                        }

                        $balance = LeaveBalance::firstOrCreate(
                            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $year],
                            [
                                'opening_balance' => 0,
                                'allocated_days' => $leaveType->days_per_year,
                                'carry_forward_days' => 0,
                                'adjusted_days' => 0,
                                'used_days' => 0,
                                'pending_days' => 0,
                                'lapsed_days' => 0,
                            ]
                        );

                        $balance->wasRecentlyCreated ? $created++ : $skipped++;
                    }
                }
            });

        $this->info("Leave balance reset for {$year}: {$created} created, {$skipped} already existed.");

        return self::SUCCESS;
    }
}
