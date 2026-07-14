<?php

namespace App\Support\Reports\Definitions;

use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\EmployeeDocument;
use App\Models\EmployeeExit;
use App\Models\EmployeeSalaryStructure;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.2 — Employee Reports. Active/Inactive Employee are aliases onto the
 * existing `reports.employees` report (its `status` filter already covers
 * them); everything else is a new generic-engine definition.
 */
class EmployeeReportDefinitions
{
    /** @return ReportDefinition[] */
    public static function make(): array
    {
        $employeeFilters = [
            'branch_id' => 'branch_id', 'department_id' => 'department_id',
            'employee_type_id' => 'employee_type_id', 'labour_type' => 'labour_type',
            'contractor_id' => 'contractor_id', 'employee_id' => 'id',
        ];
        $viaEmployeeFilters = [
            'branch_id' => 'employee.branch_id', 'department_id' => 'employee.department_id',
            'employee_type_id' => 'employee.employee_type_id', 'labour_type' => 'employee.labour_type',
            'contractor_id' => 'employee.contractor_id', 'employee_id' => 'employee_id',
        ];

        return [
            new ReportDefinition(
                key: 'active-employee',
                section: 'employee',
                title: 'Active Employee Report',
                description: 'Alias of the existing Employee Report, filtered to active employees.',
                aliasRoute: 'reports.employees',
                aliasParams: ['status' => 'active'],
            ),
            new ReportDefinition(
                key: 'inactive-employee',
                section: 'employee',
                title: 'Inactive Employee Report',
                description: 'Alias of the existing Employee Report, filtered to inactive employees.',
                aliasRoute: 'reports.employees',
                aliasParams: ['status' => 'inactive'],
            ),

            new ReportDefinition(
                key: 'employee-master',
                section: 'employee',
                title: 'Employee Master Report',
                description: 'Full common-field dump per FSD 14.2 (employee number, name, branch, type, labour type, contractor, department, designation, shift, DOB, DOJ, mobile, status, PF/ESI/TDS applicability).',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'emp_type', 'label' => 'Employee Type', 'path' => 'employeeType.name'],
                    ['key' => 'labour_type', 'label' => 'Labour Type', 'path' => 'labour_type'],
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'contractor.name'],
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'designation', 'label' => 'Designation', 'path' => 'designation.name'],
                    ['key' => 'shift', 'label' => 'Shift', 'path' => 'shift.name'],
                    ['key' => 'dob', 'label' => 'Date of Birth', 'path' => 'date_of_birth', 'format' => 'date'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'mobile', 'label' => 'Mobile Number', 'path' => 'phone'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                    ['key' => 'pf', 'label' => 'PF Applicable', 'path' => 'is_pf_applicable', 'format' => 'boolean'],
                    ['key' => 'esi', 'label' => 'ESI Applicable', 'path' => 'is_esi_applicable', 'format' => 'boolean'],
                    ['key' => 'tds', 'label' => 'TDS Applicable', 'path' => 'is_tds_applicable', 'format' => 'boolean'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'employeeType', 'contractor', 'department', 'designation', 'shift'],
                defaultSort: ['first_name', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'staff-report',
                section: 'employee',
                title: 'Staff Report',
                description: 'Employees classified as Staff (primary_employee_type = staff).',
                query: fn () => Employee::query()->staff(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'designation', 'label' => 'Designation', 'path' => 'designation.name'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'department', 'designation'],
                defaultSort: ['first_name', 'asc'],
            ),

            new ReportDefinition(
                key: 'company-labour-report',
                section: 'employee',
                title: 'Company Labour Report',
                description: 'Employees classified as Company Labour.',
                query: fn () => Employee::query()->companyLabour(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'designation', 'label' => 'Designation', 'path' => 'designation.name'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'department', 'designation'],
                defaultSort: ['first_name', 'asc'],
            ),

            new ReportDefinition(
                key: 'contract-labour-employee-report',
                section: 'employee',
                title: 'Contract Labour Report',
                description: 'Employee-sourced contract labour listing (primary_employee_type=labour, labour_type=contract_labour). Distinct from the existing wage-focused Contract Labour Report under reports.contract-labour, which is sourced from the separate ContractWorker/ContractWorkerPayroll tables.',
                query: fn () => Employee::query()->contractLabour(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'contractor.name'],
                    ['key' => 'contractor_no', 'label' => 'Contractor Employee No.', 'path' => 'contractor_employee_number'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'contractor'],
                defaultSort: ['contractor_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'contractor-wise-labour-report',
                section: 'employee',
                title: 'Contractor-Wise Labour Report',
                description: 'Employee-sourced contract labour, grouped/sorted by contractor.',
                query: fn () => Employee::query()->contractLabour()->whereNotNull('contractor_id'),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'contractor.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'labour_category', 'label' => 'Labour Category', 'path' => 'labour_category'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'contractor'],
                defaultSort: ['contractor_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'branch-wise-employee-report',
                section: 'employee',
                title: 'Branch-Wise Employee Report',
                description: 'All employees, sorted by branch.',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'emp_type', 'label' => 'Employee Type', 'path' => 'employeeType.name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'department', 'employeeType'],
                defaultSort: ['branch_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total Employees', 'value' => (clone $q)->count()],
                ],
            ),

            new ReportDefinition(
                key: 'department-wise-employee-report',
                section: 'employee',
                title: 'Department-Wise Employee Report',
                description: 'All employees, sorted by department.',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'designation', 'label' => 'Designation', 'path' => 'designation.name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'department', 'designation'],
                defaultSort: ['department_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total Employees', 'value' => (clone $q)->count()],
                ],
            ),

            new ReportDefinition(
                key: 'employee-joining-report',
                section: 'employee',
                title: 'Employee Joining Report',
                description: 'Employees by date of joining.',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'department', 'label' => 'Department', 'path' => 'department.name'],
                    ['key' => 'designation', 'label' => 'Designation', 'path' => 'designation.name'],
                    ['key' => 'doj', 'label' => 'Date of Joining', 'path' => 'date_of_joining', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                dateColumn: 'date_of_joining',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'department', 'designation'],
                defaultSort: ['date_of_joining', 'desc'],
            ),

            new ReportDefinition(
                key: 'employee-separation-report',
                section: 'employee',
                title: 'Employee Separation Report',
                description: 'Exit/separation records — resignation, termination, retirement, etc.',
                query: fn () => EmployeeExit::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'exit_type', 'label' => 'Exit Type', 'path' => 'exit_type'],
                    ['key' => 'notice_date', 'label' => 'Notice Date', 'path' => 'notice_date', 'format' => 'date'],
                    ['key' => 'last_working_date', 'label' => 'Last Working Date', 'path' => 'last_working_date', 'format' => 'date'],
                    ['key' => 'exit_date', 'label' => 'Exit Date', 'path' => 'exit_date', 'format' => 'date'],
                    ['key' => 'reason', 'label' => 'Reason', 'path' => 'reason'],
                    ['key' => 'fnf_status', 'label' => 'F&F Status', 'path' => 'full_and_final_status'],
                    ['key' => 'fnf_amount', 'label' => 'F&F Amount', 'path' => 'fnf_amount', 'format' => 'currency'],
                ],
                filterMap: $viaEmployeeFilters,
                dateColumn: 'exit_date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['exit_date', 'desc'],
            ),

            new ReportDefinition(
                key: 'employee-salary-structure-report',
                section: 'employee',
                title: 'Employee Salary Structure Report',
                description: 'Current salary structure per employee (CTC and earnings components).',
                query: fn () => EmployeeSalaryStructure::query()->where('is_current', true),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'slab', 'label' => 'Salary Slab', 'path' => 'slab.name'],
                    ['key' => 'ctc', 'label' => 'CTC', 'path' => 'ctc', 'format' => 'currency'],
                    ['key' => 'basic', 'label' => 'Basic', 'path' => 'basic_salary', 'format' => 'currency'],
                    ['key' => 'hra', 'label' => 'HRA', 'path' => 'hra', 'format' => 'currency'],
                    ['key' => 'da', 'label' => 'DA', 'path' => 'da', 'format' => 'currency'],
                    ['key' => 'ta', 'label' => 'TA', 'path' => 'ta', 'format' => 'currency'],
                    ['key' => 'medical', 'label' => 'Medical Allowance', 'path' => 'medical_allowance', 'format' => 'currency'],
                    ['key' => 'special', 'label' => 'Special Allowance', 'path' => 'special_allowance', 'format' => 'currency'],
                    ['key' => 'gross', 'label' => 'Gross Salary', 'path' => 'gross_salary', 'format' => 'currency'],
                    ['key' => 'effective_from', 'label' => 'Effective From', 'path' => 'effective_from', 'format' => 'date'],
                ],
                filterMap: $viaEmployeeFilters,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'slab'],
                defaultSort: ['employee_id', 'asc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'employee-statutory-details-report',
                section: 'employee',
                title: 'Employee Statutory Details Report',
                description: 'PAN/UAN/PF/ESI numbers and applicability flags. Sensitive number columns are masked unless the viewer has the "View Sensitive" report permission.',
                query: fn () => Employee::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'pan', 'label' => 'PAN Number', 'path' => 'pan_number', 'sensitive' => true],
                    ['key' => 'uan', 'label' => 'UAN Number', 'path' => 'uan_number', 'sensitive' => true],
                    ['key' => 'pf_number', 'label' => 'PF Number', 'path' => 'pf_number', 'sensitive' => true],
                    ['key' => 'esi_number', 'label' => 'ESI Number', 'path' => 'esi_number', 'sensitive' => true],
                    ['key' => 'aadhaar', 'label' => 'Aadhaar Number', 'path' => 'aadhaar_number', 'sensitive' => true],
                    ['key' => 'pf_applicable', 'label' => 'PF Applicable', 'path' => 'is_pf_applicable', 'format' => 'boolean'],
                    ['key' => 'esi_applicable', 'label' => 'ESI Applicable', 'path' => 'is_esi_applicable', 'format' => 'boolean'],
                    ['key' => 'tds_applicable', 'label' => 'TDS Applicable', 'path' => 'is_tds_applicable', 'format' => 'boolean'],
                ],
                filterMap: $employeeFilters,
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['first_name', 'asc'],
            ),

            new ReportDefinition(
                key: 'employee-bank-details-report',
                section: 'employee',
                title: 'Employee Bank Details Report',
                description: 'Bank account details per employee. Account numbers are masked unless the viewer has the "View Sensitive" report permission.',
                query: fn () => EmployeeBankDetail::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'bank_name', 'label' => 'Bank Name', 'path' => 'bank_name'],
                    ['key' => 'account_number', 'label' => 'Account Number', 'path' => 'account_number', 'sensitive' => true],
                    ['key' => 'ifsc', 'label' => 'IFSC Code', 'path' => 'ifsc_code'],
                    ['key' => 'account_type', 'label' => 'Account Type', 'path' => 'account_type'],
                    ['key' => 'payment_mode', 'label' => 'Payment Mode', 'path' => 'payment_mode'],
                    ['key' => 'is_primary', 'label' => 'Primary', 'path' => 'is_primary', 'format' => 'boolean'],
                ],
                filterMap: $viaEmployeeFilters,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'expiring-employee-documents-report',
                section: 'employee',
                title: 'Expiring Employee Documents Report',
                description: 'Employee documents with an expiry date, ordered by soonest expiry. Use the date range filter to narrow to an upcoming window.',
                query: fn () => EmployeeDocument::query()->whereNotNull('expiry_date'),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'document_type', 'label' => 'Document Type', 'path' => 'document_type'],
                    ['key' => 'document_number', 'label' => 'Document Number', 'path' => 'document_number', 'sensitive' => true],
                    ['key' => 'expiry_date', 'label' => 'Expiry Date', 'path' => 'expiry_date', 'format' => 'date'],
                    ['key' => 'is_verified', 'label' => 'Verified', 'path' => 'is_verified', 'format' => 'boolean'],
                ],
                filterMap: $viaEmployeeFilters,
                dateColumn: 'expiry_date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['expiry_date', 'asc'],
            ),

            new ReportDefinition(
                key: 'contract-expiry-report',
                section: 'employee',
                title: 'Contract Expiry Report',
                description: 'Employees with a fixed-term contract end date, ordered by soonest expiry.',
                query: fn () => Employee::query()->whereNotNull('contract_end_date'),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'contract_start', 'label' => 'Contract Start', 'path' => 'contract_start_date', 'format' => 'date'],
                    ['key' => 'contract_end', 'label' => 'Contract End', 'path' => 'contract_end_date', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Employment Status', 'path' => 'status'],
                ],
                filterMap: $employeeFilters,
                dateColumn: 'contract_end_date',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['contract_end_date', 'asc'],
            ),
        ];
    }
}
