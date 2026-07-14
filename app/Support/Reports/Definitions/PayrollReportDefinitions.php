<?php

namespace App\Support\Reports\Definitions;

use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollRecord;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.5 — Payroll Reports. Payroll Register, Employee-Wise Payroll,
 * Contract Labour Payroll, Contractor-Wise Payroll, LOP Deduction, and
 * Overtime Payment are aliases onto existing reports; the rest are new
 * generic-engine definitions.
 */
class PayrollReportDefinitions
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
            new ReportDefinition(key: 'payroll-register', section: 'payroll', title: 'Payroll Register', description: 'Alias of the existing Payroll Report.', aliasRoute: 'reports.payroll'),
            new ReportDefinition(key: 'employee-wise-payroll', section: 'payroll', title: 'Employee-Wise Payroll Report', description: 'Alias of the existing Payroll Report (already row-per-employee).', aliasRoute: 'reports.payroll'),
            new ReportDefinition(key: 'contract-labour-payroll', section: 'payroll', title: 'Contract Labour Payroll Report', description: 'Alias of the existing Contract Labour Report.', aliasRoute: 'reports.contract-labour'),
            new ReportDefinition(key: 'contractor-wise-payroll', section: 'payroll', title: 'Contractor-Wise Payroll Report', description: 'Alias of the existing Contractor Report.', aliasRoute: 'reports.contractor'),
            new ReportDefinition(key: 'lop-deduction-report', section: 'payroll', title: 'LOP Deduction Report', description: 'Alias of the existing LOP Report.', aliasRoute: 'reports.lop'),
            new ReportDefinition(key: 'overtime-payment-report', section: 'payroll', title: 'Overtime Payment Report', description: 'Alias of the existing Overtime Report.', aliasRoute: 'reports.overtime'),

            new ReportDefinition(
                key: 'branch-wise-payroll-summary',
                section: 'payroll',
                title: 'Branch-Wise Payroll Summary',
                description: 'Payroll records sorted by branch.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'deductions', 'label' => 'Total Deductions', 'path' => 'total_deductions', 'format' => 'currency'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Gross', 'value' => number_format((clone $q)->sum('gross_earnings'), 2)],
                    ['label' => 'Deductions', 'value' => number_format((clone $q)->sum('total_deductions'), 2)],
                    ['label' => 'Net', 'value' => number_format((clone $q)->sum('net_salary'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'department-wise-payroll-summary',
                section: 'payroll',
                title: 'Department-Wise Payroll Summary',
                description: 'Payroll records sorted by department.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'department', 'label' => 'Department', 'path' => 'employee.department.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'deductions', 'label' => 'Total Deductions', 'path' => 'total_deductions', 'format' => 'currency'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'employee.department'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Gross', 'value' => number_format((clone $q)->sum('gross_earnings'), 2)],
                    ['label' => 'Net', 'value' => number_format((clone $q)->sum('net_salary'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'staff-payroll-report',
                section: 'payroll',
                title: 'Staff Payroll Report',
                description: 'Payroll records for employees classified as Staff.',
                query: fn () => PayrollRecord::query()->whereHas('employee', fn ($q) => $q->staff()),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'company-labour-payroll-report',
                section: 'payroll',
                title: 'Company Labour Payroll Report',
                description: 'Payroll records for employees classified as Company Labour.',
                query: fn () => PayrollRecord::query()->whereHas('employee', fn ($q) => $q->companyLabour()),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'earnings-report',
                section: 'payroll',
                title: 'Earnings Report',
                description: 'Earnings component breakdown (basic/HRA/DA/TA/medical/special/OT/other) per payroll record.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'basic', 'label' => 'Basic', 'path' => 'basic_salary', 'format' => 'currency'],
                    ['key' => 'hra', 'label' => 'HRA', 'path' => 'hra', 'format' => 'currency'],
                    ['key' => 'da', 'label' => 'DA', 'path' => 'da', 'format' => 'currency'],
                    ['key' => 'ta', 'label' => 'TA', 'path' => 'ta', 'format' => 'currency'],
                    ['key' => 'medical', 'label' => 'Medical Allowance', 'path' => 'medical_allowance', 'format' => 'currency'],
                    ['key' => 'special', 'label' => 'Special Allowance', 'path' => 'special_allowance', 'format' => 'currency'],
                    ['key' => 'ot', 'label' => 'OT Amount', 'path' => 'ot_amount', 'format' => 'currency'],
                    ['key' => 'other', 'label' => 'Other Earnings', 'path' => 'other_earnings', 'format' => 'currency'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'deduction-report',
                section: 'payroll',
                title: 'Deduction Report',
                description: 'Deduction component breakdown (PF/ESI/TDS/advance/LOP/other) per payroll record.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'pf', 'label' => 'PF (Employee)', 'path' => 'pf_employee', 'format' => 'currency'],
                    ['key' => 'esi', 'label' => 'ESI (Employee)', 'path' => 'esi_employee', 'format' => 'currency'],
                    ['key' => 'tds', 'label' => 'TDS', 'path' => 'tds', 'format' => 'currency'],
                    ['key' => 'advance', 'label' => 'Advance', 'path' => 'advance_deduction', 'format' => 'currency'],
                    ['key' => 'lop', 'label' => 'LOP Deduction', 'path' => 'lop_deduction', 'format' => 'currency'],
                    ['key' => 'other', 'label' => 'Other Deductions', 'path' => 'other_deductions', 'format' => 'currency'],
                    ['key' => 'total', 'label' => 'Total Deductions', 'path' => 'total_deductions', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'net-salary-report',
                section: 'payroll',
                title: 'Net Salary Report',
                description: 'Gross earnings, total deductions, and net salary per employee.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'deductions', 'label' => 'Total Deductions', 'path' => 'total_deductions', 'format' => 'currency'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'bank-transfer-statement-report',
                section: 'payroll',
                title: 'Bank Transfer Statement',
                description: 'Net salary payable per employee paid via bank transfer, with their primary bank account (unmasked to permitted viewers only). This is the Reports-module equivalent of the existing Payroll module export.',
                query: fn () => PayrollRecord::query()->whereHas('employee.bankDetails', fn ($q) => $q->where('payment_mode', 'bank_transfer')),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'bank_name', 'label' => 'Bank Name', 'path' => 'employee.bankDetails.0.bank_name'],
                    ['key' => 'account_number', 'label' => 'Account Number', 'path' => 'employee.bankDetails.0.account_number', 'sensitive' => true],
                    ['key' => 'ifsc', 'label' => 'IFSC Code', 'path' => 'employee.bankDetails.0.ifsc_code'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'employee.bankDetails'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'cash-salary-statement-report',
                section: 'payroll',
                title: 'Cash Salary Statement',
                description: 'Net salary payable per employee paid in cash.',
                query: fn () => PayrollRecord::query()->whereHas('employee.bankDetails', fn ($q) => $q->where('payment_mode', 'cash')),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'payroll-adjustment-earnings-report',
                section: 'payroll',
                title: 'Payroll Adjustment Report — Earnings',
                description: 'Manually added earning line items on a payroll record, with the required reason.',
                query: fn () => PayrollAllowance::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'payroll.employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'payroll.employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'payroll.month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'payroll.year'],
                    ['key' => 'item', 'label' => 'Earning', 'path' => 'name'],
                    ['key' => 'amount', 'label' => 'Amount', 'path' => 'amount', 'format' => 'currency'],
                    ['key' => 'remarks', 'label' => 'Reason', 'path' => 'remarks'],
                ],
                filterMap: [
                    'branch_id' => 'payroll.employee.branch_id', 'department_id' => 'payroll.employee.department_id',
                    'employee_type_id' => 'payroll.employee.employee_type_id', 'labour_type' => 'payroll.employee.labour_type',
                    'contractor_id' => 'payroll.employee.contractor_id', 'employee_id' => 'payroll.employee_id',
                ],
                branchScope: ['type' => 'via', 'relation' => 'payroll.employee'],
                eagerLoads: ['payroll.employee.branch'],
                defaultSort: ['id', 'desc'],
            ),

            new ReportDefinition(
                key: 'payroll-adjustment-deductions-report',
                section: 'payroll',
                title: 'Payroll Adjustment Report — Deductions',
                description: 'Manually added deduction line items on a payroll record, with the required reason.',
                query: fn () => PayrollDeduction::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'payroll.employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'payroll.employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'payroll.month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'payroll.year'],
                    ['key' => 'item', 'label' => 'Deduction', 'path' => 'name'],
                    ['key' => 'amount', 'label' => 'Amount', 'path' => 'amount', 'format' => 'currency'],
                    ['key' => 'remarks', 'label' => 'Reason', 'path' => 'remarks'],
                ],
                filterMap: [
                    'branch_id' => 'payroll.employee.branch_id', 'department_id' => 'payroll.employee.department_id',
                    'employee_type_id' => 'payroll.employee.employee_type_id', 'labour_type' => 'payroll.employee.labour_type',
                    'contractor_id' => 'payroll.employee.contractor_id', 'employee_id' => 'payroll.employee_id',
                ],
                branchScope: ['type' => 'via', 'relation' => 'payroll.employee'],
                eagerLoads: ['payroll.employee.branch'],
                defaultSort: ['id', 'desc'],
            ),

            new ReportDefinition(
                key: 'employer-cost-report',
                section: 'payroll',
                title: 'Employer Cost Report',
                description: 'Total employer cost (gross + employer PF + employer ESI) per payroll record.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'gross', 'label' => 'Gross Earnings', 'path' => 'gross_earnings', 'format' => 'currency'],
                    ['key' => 'pf_employer', 'label' => 'Employer PF', 'path' => 'pf_employer', 'format' => 'currency'],
                    ['key' => 'esi_employer', 'label' => 'Employer ESI', 'path' => 'esi_employer', 'format' => 'currency'],
                    ['key' => 'employer_cost', 'label' => 'Total Employer Cost', 'path' => 'employer_cost', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total Employer Cost', 'value' => number_format((clone $q)->sum('employer_cost'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'payslip-register-report',
                section: 'payroll',
                title: 'Payslip Register',
                description: 'Which payroll records exist for a period and their lifecycle status (calculated/confirmed/closed/paid).',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                    ['key' => 'generated_at', 'label' => 'Generated At', 'path' => 'generated_at', 'format' => 'datetime'],
                    ['key' => 'net', 'label' => 'Net Salary', 'path' => 'net_salary', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                statusColumn: 'status',
                statusOptions: ['draft' => 'Draft', 'calculated' => 'Calculated', 'confirmed' => 'Confirmed', 'closed' => 'Closed', 'paid' => 'Paid', 'hold' => 'Hold'],
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),
        ];
    }
}
