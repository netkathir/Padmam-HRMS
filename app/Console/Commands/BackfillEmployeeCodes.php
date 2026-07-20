<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * One-time (safely re-runnable) backfill for employees created before
 * Employee Code generation became automatic at creation time. Assigns a
 * code to every employee that still doesn't have one, using the exact same
 * Rule Engine / Employee-Type-prefixed sequence resolution as a fresh
 * employee creation (EmployeeNumberGenerator::resolveEmployeeCode()).
 * Employees that already have a code are left untouched.
 */
class BackfillEmployeeCodes extends Command
{
    protected $signature = 'employees:backfill-codes';

    protected $description = "Assign an Employee Code to every existing employee that still doesn't have one";

    public function handle(EmployeeNumberGenerator $generator): int
    {
        $employees = Employee::where(fn ($q) => $q->whereNull('employee_code')->orWhere('employee_code', ''))->get();

        if ($employees->isEmpty()) {
            $this->info('No employees are missing an Employee Code.');

            return self::SUCCESS;
        }

        $labourTypeMap = ['staff' => null, 'company_labour' => 'company_labour', 'contract_labour' => 'contract_labour'];

        foreach ($employees as $employee) {
            $category = $employee->primary_employee_type === 'staff' ? 'staff' : $employee->labour_type;
            $labourType = $labourTypeMap[$category] ?? $employee->labour_type;

            $data = [
                'branch_id'        => $employee->branch_id,
                'contractor_id'    => $employee->contractor_id,
                'employee_type_id' => $employee->employee_type_id,
            ];

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $code = $generator->resolveEmployeeCode($data, $employee->primary_employee_type, $labourType);
                try {
                    $employee->update(['employee_code' => $code]);
                    $this->line("#{$employee->id} {$employee->full_name} -> {$code}");
                    break;
                } catch (QueryException $e) {
                    if ((string) $e->getCode() !== '23000' || $attempt === 5) {
                        throw $e;
                    }
                }
            }
        }

        $this->info("Backfilled {$employees->count()} employee(s).");

        return self::SUCCESS;
    }
}
