<?php

namespace App\Support\Reports;

use App\Support\Reports\Definitions\AttendanceReportDefinitions;
use App\Support\Reports\Definitions\ContractorReportDefinitions;
use App\Support\Reports\Definitions\EmployeeReportDefinitions;
use App\Support\Reports\Definitions\LeaveLopReportDefinitions;
use App\Support\Reports\Definitions\PayrollReportDefinitions;
use App\Support\Reports\Definitions\StatutoryReportDefinitions;

/**
 * Aggregates the per-FSD-subsection definition files (one file per 14.2-14.7
 * domain, matching this repo's existing "one file per concern" convention)
 * into a single flat registry keyed by report key.
 */
class ReportRegistry
{
    /** @var ReportDefinition[]|null */
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        return self::$cache = array_merge(
            EmployeeReportDefinitions::make(),
            AttendanceReportDefinitions::make(),
            LeaveLopReportDefinitions::make(),
            PayrollReportDefinitions::make(),
            StatutoryReportDefinitions::make(),
            ContractorReportDefinitions::make(),
        );
    }

    public static function find(string $key): ?ReportDefinition
    {
        foreach (self::all() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    public static function bySection(string $section): array
    {
        return array_values(array_filter(self::all(), fn ($d) => $d->section === $section));
    }

    public static function sections(): array
    {
        return [
            'employee'   => 'Employee Reports',
            'attendance' => 'Attendance Reports',
            'leave_lop'  => 'Leave & LOP Reports',
            'payroll'    => 'Payroll Reports',
            'statutory'  => 'Statutory Reports',
            'contractor' => 'Contractor Reports',
        ];
    }
}
