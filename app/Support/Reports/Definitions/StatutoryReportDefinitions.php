<?php

namespace App\Support\Reports\Definitions;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\SalarySlab;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.6 — Statutory Reports. All sourced from PayrollRecord (the actual
 * amounts applied at generation time, captured in applied_rules) rather than
 * re-derived from current SalarySlab/PfEsiConfig/Rule Engine master data,
 * which may have changed since a given period was processed.
 */
class StatutoryReportDefinitions
{
    private const VIA_EMPLOYEE_FILTERS = [
        'branch_id' => 'employee.branch_id', 'department_id' => 'employee.department_id',
        'employee_type_id' => 'employee.employee_type_id', 'labour_type' => 'employee.labour_type',
        'contractor_id' => 'employee.contractor_id', 'employee_id' => 'employee_id',
    ];

    /** @return ReportDefinition[] */
    public static function make(): array
    {
        $viaEmployee = self::VIA_EMPLOYEE_FILTERS;

        return [
            new ReportDefinition(
                key: 'pf-employee-contribution-report',
                section: 'statutory',
                title: 'PF Employee Contribution Report',
                description: 'Employee-side PF contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where('pf_employee', '>', 0),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'pf_employee', 'label' => 'PF (Employee)', 'path' => 'pf_employee', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [['label' => 'Total PF (Employee)', 'value' => number_format((clone $q)->sum('pf_employee'), 2)]],
            ),

            new ReportDefinition(
                key: 'pf-employer-contribution-report',
                section: 'statutory',
                title: 'PF Employer Contribution Report',
                description: 'Employer-side PF contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where('pf_employer', '>', 0),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'pf_employer', 'label' => 'PF (Employer)', 'path' => 'pf_employer', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [['label' => 'Total PF (Employer)', 'value' => number_format((clone $q)->sum('pf_employer'), 2)]],
            ),

            new ReportDefinition(
                key: 'pf-consolidated-report',
                section: 'statutory',
                title: 'PF Consolidated Report',
                description: 'Employee + employer PF contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where(fn ($q) => $q->where('pf_employee', '>', 0)->orWhere('pf_employer', '>', 0)),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'pf_employee', 'label' => 'PF (Employee)', 'path' => 'pf_employee', 'format' => 'currency'],
                    ['key' => 'pf_employer', 'label' => 'PF (Employer)', 'path' => 'pf_employer', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total PF (Employee)', 'value' => number_format((clone $q)->sum('pf_employee'), 2)],
                    ['label' => 'Total PF (Employer)', 'value' => number_format((clone $q)->sum('pf_employer'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'esi-employee-contribution-report',
                section: 'statutory',
                title: 'ESI Employee Contribution Report',
                description: 'Employee-side ESI contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where('esi_employee', '>', 0),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'esi_employee', 'label' => 'ESI (Employee)', 'path' => 'esi_employee', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [['label' => 'Total ESI (Employee)', 'value' => number_format((clone $q)->sum('esi_employee'), 2)]],
            ),

            new ReportDefinition(
                key: 'esi-employer-contribution-report',
                section: 'statutory',
                title: 'ESI Employer Contribution Report',
                description: 'Employer-side ESI contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where('esi_employer', '>', 0),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'esi_employer', 'label' => 'ESI (Employer)', 'path' => 'esi_employer', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [['label' => 'Total ESI (Employer)', 'value' => number_format((clone $q)->sum('esi_employer'), 2)]],
            ),

            new ReportDefinition(
                key: 'esi-consolidated-report',
                section: 'statutory',
                title: 'ESI Consolidated Report',
                description: 'Employee + employer ESI contribution per payroll record, as actually applied at generation time.',
                query: fn () => PayrollRecord::query()->where(fn ($q) => $q->where('esi_employee', '>', 0)->orWhere('esi_employer', '>', 0)),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'esi_employee', 'label' => 'ESI (Employee)', 'path' => 'esi_employee', 'format' => 'currency'],
                    ['key' => 'esi_employer', 'label' => 'ESI (Employer)', 'path' => 'esi_employer', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total ESI (Employee)', 'value' => number_format((clone $q)->sum('esi_employee'), 2)],
                    ['label' => 'Total ESI (Employer)', 'value' => number_format((clone $q)->sum('esi_employer'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'tds-deduction-report',
                section: 'statutory',
                title: 'TDS Deduction Report',
                description: 'TDS deducted per payroll record, as actually applied at generation time. PAN is masked unless the viewer has the "View Sensitive" report permission.',
                query: fn () => PayrollRecord::query()->where('tds', '>', 0),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'pan', 'label' => 'PAN Number', 'path' => 'employee.pan_number', 'sensitive' => true],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'tds', 'label' => 'TDS', 'path' => 'tds', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [['label' => 'Total TDS', 'value' => number_format((clone $q)->sum('tds'), 2)]],
            ),

            new ReportDefinition(
                key: 'salary-slab-wise-statutory-report',
                section: 'statutory',
                title: 'Salary Slab-Wise Statutory Report',
                description: 'Configured PF/ESI/TDS percentages and wage ceilings per Salary Slab (current master configuration, not a per-payslip figure).',
                query: fn () => SalarySlab::query(),
                columns: [
                    ['key' => 'name', 'label' => 'Slab Name', 'path' => 'name'],
                    ['key' => 'min_ctc', 'label' => 'Min CTC', 'path' => 'min_ctc', 'format' => 'currency'],
                    ['key' => 'max_ctc', 'label' => 'Max CTC', 'path' => 'max_ctc', 'format' => 'currency'],
                    ['key' => 'pf_employee_pct', 'label' => 'PF Employee %', 'path' => 'pf_employee_percentage'],
                    ['key' => 'pf_employer_pct', 'label' => 'PF Employer %', 'path' => 'pf_employer_percentage'],
                    ['key' => 'esi_employee_pct', 'label' => 'ESI Employee %', 'path' => 'esi_employee_percentage'],
                    ['key' => 'esi_employer_pct', 'label' => 'ESI Employer %', 'path' => 'esi_employer_percentage'],
                    ['key' => 'tds_pct', 'label' => 'TDS %', 'path' => 'tds_percentage'],
                    ['key' => 'is_active', 'label' => 'Active', 'path' => 'is_active', 'format' => 'boolean'],
                ],
                defaultSort: ['min_ctc', 'asc'],
            ),

            new ReportDefinition(
                key: 'branch-wise-statutory-summary',
                section: 'statutory',
                title: 'Branch-Wise Statutory Summary',
                description: 'PF/ESI/TDS amounts per payroll record, sorted by branch, as actually applied at generation time.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'pf_employee', 'label' => 'PF (Employee)', 'path' => 'pf_employee', 'format' => 'currency'],
                    ['key' => 'pf_employer', 'label' => 'PF (Employer)', 'path' => 'pf_employer', 'format' => 'currency'],
                    ['key' => 'esi_employee', 'label' => 'ESI (Employee)', 'path' => 'esi_employee', 'format' => 'currency'],
                    ['key' => 'esi_employer', 'label' => 'ESI (Employer)', 'path' => 'esi_employer', 'format' => 'currency'],
                    ['key' => 'tds', 'label' => 'TDS', 'path' => 'tds', 'format' => 'currency'],
                ],
                filterMap: [
                    'branch_id' => 'employee.branch_id', 'department_id' => 'employee.department_id',
                    'employee_type_id' => 'employee.employee_type_id', 'labour_type' => 'employee.labour_type',
                    'contractor_id' => 'employee.contractor_id', 'employee_id' => 'employee_id',
                ],
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total PF', 'value' => number_format((clone $q)->sum('pf_employee') + (clone $q)->sum('pf_employer'), 2)],
                    ['label' => 'Total ESI', 'value' => number_format((clone $q)->sum('esi_employee') + (clone $q)->sum('esi_employer'), 2)],
                    ['label' => 'Total TDS', 'value' => number_format((clone $q)->sum('tds'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'employee-statutory-applicability-report',
                section: 'statutory',
                title: 'Employee Statutory Applicability Report',
                description: 'Per-employee PF/ESI/TDS applicability flags.',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'emp_type', 'label' => 'Employee Type', 'path' => 'employeeType.name'],
                    ['key' => 'pf_applicable', 'label' => 'PF Applicable', 'path' => 'is_pf_applicable', 'format' => 'boolean'],
                    ['key' => 'esi_applicable', 'label' => 'ESI Applicable', 'path' => 'is_esi_applicable', 'format' => 'boolean'],
                    ['key' => 'tds_applicable', 'label' => 'TDS Applicable', 'path' => 'is_tds_applicable', 'format' => 'boolean'],
                ],
                filterMap: [
                    'branch_id' => 'branch_id', 'department_id' => 'department_id',
                    'employee_type_id' => 'employee_type_id', 'labour_type' => 'labour_type',
                    'contractor_id' => 'contractor_id', 'employee_id' => 'id',
                ],
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'employeeType'],
                defaultSort: ['first_name', 'asc'],
            ),
        ];
    }
}
