<?php

namespace App\Support\Reports\Definitions;

use App\Models\Contractor;
use App\Models\ContractWorkerAttendance;
use App\Models\ContractWorkerPayroll;
use App\Models\PayrollRecord;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.7 — Contractor Reports. Contractor Labour Strength and Contractor
 * Payroll Report are aliases onto the existing reports.contractor; the rest
 * are new generic-engine definitions. Attendance/LOP here are sourced from
 * the independent ContractWorker system (own attendance/payroll tables, no
 * FK to Employee) — see the Employee-sourced counterparts under Attendance/
 * Leave & LOP Reports for company-recorded contract labour.
 */
class ContractorReportDefinitions
{
    /** @return ReportDefinition[] */
    public static function make(): array
    {
        return [
            new ReportDefinition(key: 'contractor-labour-strength', section: 'contractor', title: 'Contractor Labour Strength Report', description: 'Alias of the existing Contractor Report.', aliasRoute: 'reports.contractor'),
            new ReportDefinition(key: 'contractor-payroll-report-alias', section: 'contractor', title: 'Contractor Payroll Report', description: 'Alias of the existing Contractor Report.', aliasRoute: 'reports.contractor'),

            new ReportDefinition(
                key: 'contractor-master-report',
                section: 'contractor',
                title: 'Contractor Master Report',
                description: 'Contractor master data — licence, GST, PAN, PF/ESI registration, agreement dates. Sensitive registration numbers are masked unless the viewer has the "View Sensitive" report permission.',
                query: fn () => Contractor::query(),
                columns: [
                    ['key' => 'name', 'label' => 'Name', 'path' => 'name'],
                    ['key' => 'code', 'label' => 'Code', 'path' => 'code'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'contact_person', 'label' => 'Contact Person', 'path' => 'contact_person'],
                    ['key' => 'phone', 'label' => 'Phone', 'path' => 'phone'],
                    ['key' => 'license_number', 'label' => 'Licence Number', 'path' => 'license_number', 'sensitive' => true],
                    ['key' => 'gst_number', 'label' => 'GST Number', 'path' => 'gst_number', 'sensitive' => true],
                    ['key' => 'pan_number', 'label' => 'PAN Number', 'path' => 'pan_number', 'sensitive' => true],
                    ['key' => 'pf_reg', 'label' => 'PF Registration No.', 'path' => 'pf_registration_number', 'sensitive' => true],
                    ['key' => 'esi_reg', 'label' => 'ESI Registration No.', 'path' => 'esi_registration_number', 'sensitive' => true],
                    ['key' => 'license_expiry', 'label' => 'Licence Expiry', 'path' => 'license_expiry', 'format' => 'date'],
                    ['key' => 'agreement_end', 'label' => 'Agreement End', 'path' => 'agreement_end_date', 'format' => 'date'],
                    ['key' => 'is_active', 'label' => 'Active', 'path' => 'is_active', 'format' => 'boolean'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['name', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'contractor-branch-mapping-report',
                section: 'contractor',
                title: 'Contractor Branch Mapping Report',
                description: 'Which branches each contractor is mapped to (additive multi-branch applicability, alongside the contractor\'s single primary branch).',
                query: fn () => Contractor::query(),
                columns: [
                    ['key' => 'name', 'label' => 'Contractor', 'path' => 'name'],
                    ['key' => 'primary_branch', 'label' => 'Primary Branch', 'path' => 'branch.name'],
                    ['key' => 'mapped_branches', 'label' => 'Mapped Branches', 'path' => 'branches'],
                    ['key' => 'is_active', 'label' => 'Active', 'path' => 'is_active', 'format' => 'boolean'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'branches'],
                defaultSort: ['name', 'asc'],
            ),

            new ReportDefinition(
                key: 'contractor-attendance-report',
                section: 'contractor',
                title: 'Contractor Attendance Report',
                description: 'Attendance for Independent Contract Workers (the separate ContractWorker system — no FK to the Employee table). Company-recorded contract labour attendance is a separate report — see Contractor-Wise Attendance Report under Attendance Reports.',
                query: fn () => ContractWorkerAttendance::query(),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'contractor.name'],
                    ['key' => 'worker', 'label' => 'Worker', 'path' => 'worker.name'],
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                    ['key' => 'in_time', 'label' => 'In Time', 'path' => 'in_time'],
                    ['key' => 'out_time', 'label' => 'Out Time', 'path' => 'out_time'],
                    ['key' => 'ot_hours', 'label' => 'OT Hours', 'path' => 'ot_hours'],
                ],
                filterMap: ['branch_id' => 'contractor.branch_id', 'contractor_id' => 'contractor_id'],
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'contractor'],
                eagerLoads: ['contractor', 'worker'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'contractor-lop-report',
                section: 'contractor',
                title: 'Contractor LOP Report',
                description: 'Independent Contract Worker payroll has no distinct LOP field — absent/half days are shown as the closest equivalent. Company-recorded contract labour LOP (a true LOP figure) is a separate report — see Contractor-Wise LOP Summary under Leave & LOP Reports.',
                query: fn () => ContractWorkerPayroll::query()->where(fn ($q) => $q->where('absent_days', '>', 0)->orWhere('half_days', '>', 0)),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'contractor.name'],
                    ['key' => 'worker', 'label' => 'Worker', 'path' => 'worker.name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'absent_days', 'label' => 'Absent Days', 'path' => 'absent_days'],
                    ['key' => 'half_days', 'label' => 'Half Days', 'path' => 'half_days'],
                    ['key' => 'deductions', 'label' => 'Deductions', 'path' => 'deductions', 'format' => 'currency'],
                ],
                filterMap: ['branch_id' => 'contractor.branch_id', 'contractor_id' => 'contractor_id'],
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'contractor'],
                eagerLoads: ['contractor', 'worker'],
                defaultSort: ['contractor_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'contractor-statutory-contribution-report',
                section: 'contractor',
                title: 'Contractor Statutory Contribution Report',
                description: 'PF/ESI/TDS for company-recorded contract labour (Employee-sourced payroll). Independent Contract Worker wages have no statutory breakdown in this system (ContractWorkerPayroll stores only gross/deductions/net).',
                query: fn () => PayrollRecord::query()->whereHas('employee', fn ($q) => $q->whereNotNull('contractor_id')),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'employee.contractor.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'pf_employee', 'label' => 'PF (Employee)', 'path' => 'pf_employee', 'format' => 'currency'],
                    ['key' => 'pf_employer', 'label' => 'PF (Employer)', 'path' => 'pf_employer', 'format' => 'currency'],
                    ['key' => 'esi_employee', 'label' => 'ESI (Employee)', 'path' => 'esi_employee', 'format' => 'currency'],
                    ['key' => 'esi_employer', 'label' => 'ESI (Employer)', 'path' => 'esi_employer', 'format' => 'currency'],
                    ['key' => 'tds', 'label' => 'TDS', 'path' => 'tds', 'format' => 'currency'],
                ],
                filterMap: [
                    'branch_id' => 'employee.branch_id', 'contractor_id' => 'employee.contractor_id',
                    'employee_id' => 'employee_id',
                ],
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'employee.contractor'],
                defaultSort: ['employee_id', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'contractor-agreement-expiry-report',
                section: 'contractor',
                title: 'Contractor Agreement Expiry Report',
                description: 'Contractors with an agreement end date, ordered by soonest expiry. Sourced from Contractor.agreement_end_date — contractor documents have no expiry date of their own.',
                query: fn () => Contractor::query()->whereNotNull('agreement_end_date'),
                columns: [
                    ['key' => 'name', 'label' => 'Contractor', 'path' => 'name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'agreement_start', 'label' => 'Agreement Start', 'path' => 'agreement_start_date', 'format' => 'date'],
                    ['key' => 'agreement_end', 'label' => 'Agreement End', 'path' => 'agreement_end_date', 'format' => 'date'],
                    ['key' => 'is_active', 'label' => 'Active', 'path' => 'is_active', 'format' => 'boolean'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                dateColumn: 'agreement_end_date',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['agreement_end_date', 'asc'],
            ),

            new ReportDefinition(
                key: 'contractor-licence-expiry-report',
                section: 'contractor',
                title: 'Contractor Licence Expiry Report',
                description: 'Contractors with a licence expiry date, ordered by soonest expiry. Sourced from Contractor.license_expiry — contractor documents have no expiry date of their own.',
                query: fn () => Contractor::query()->whereNotNull('license_expiry'),
                columns: [
                    ['key' => 'name', 'label' => 'Contractor', 'path' => 'name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'license_number', 'label' => 'Licence Number', 'path' => 'license_number', 'sensitive' => true],
                    ['key' => 'license_expiry', 'label' => 'Licence Expiry', 'path' => 'license_expiry', 'format' => 'date'],
                    ['key' => 'is_active', 'label' => 'Active', 'path' => 'is_active', 'format' => 'boolean'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                dateColumn: 'license_expiry',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['license_expiry', 'asc'],
            ),
        ];
    }
}
