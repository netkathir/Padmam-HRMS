<?php

namespace App\Support\Reports\Definitions;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.4 — Leave and LOP Reports. Leave Register and Employee-Wise LOP
 * Report are aliases onto the existing reports.leave/reports.lop; the rest
 * are new generic-engine definitions.
 */
class LeaveLopReportDefinitions
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
                key: 'leave-register',
                section: 'leave_lop',
                title: 'Leave Register',
                description: 'Alias of the existing Leave Report.',
                aliasRoute: 'reports.leave',
            ),
            new ReportDefinition(
                key: 'employee-wise-lop',
                section: 'leave_lop',
                title: 'Employee-Wise LOP Report',
                description: 'Alias of the existing LOP Report (already employee-wise).',
                aliasRoute: 'reports.lop',
            ),

            new ReportDefinition(
                key: 'leave-balance-report',
                section: 'leave_lop',
                title: 'Leave Balance Report',
                description: 'Opening/allocated/carry-forward/adjusted/used/pending/lapsed balance per employee per leave type, for the given year.',
                query: fn () => LeaveBalance::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'leave_type', 'label' => 'Leave Type', 'path' => 'leaveType.name'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'opening', 'label' => 'Opening Balance', 'path' => 'opening_balance'],
                    ['key' => 'allocated', 'label' => 'Allocated', 'path' => 'allocated_days'],
                    ['key' => 'carry_forward', 'label' => 'Carry Forward', 'path' => 'carry_forward_days'],
                    ['key' => 'adjusted', 'label' => 'Adjusted', 'path' => 'adjusted_days'],
                    ['key' => 'used', 'label' => 'Used', 'path' => 'used_days'],
                    ['key' => 'pending', 'label' => 'Pending', 'path' => 'pending_days'],
                    ['key' => 'lapsed', 'label' => 'Lapsed', 'path' => 'lapsed_days'],
                    ['key' => 'balance', 'label' => 'Available Balance', 'path' => 'balance_days'],
                ],
                filterMap: $viaEmployee,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'leaveType'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'leave-availed-report',
                section: 'leave_lop',
                title: 'Leave Availed Report',
                description: 'Approved leave requests, by date taken.',
                query: fn () => LeaveRequest::query()->where('status', 'approved'),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'leave_type', 'label' => 'Leave Type', 'path' => 'leaveType.name'],
                    ['key' => 'start_date', 'label' => 'From', 'path' => 'start_date', 'format' => 'date'],
                    ['key' => 'end_date', 'label' => 'To', 'path' => 'end_date', 'format' => 'date'],
                    ['key' => 'days', 'label' => 'Days', 'path' => 'total_days'],
                    ['key' => 'half_day', 'label' => 'Half Day', 'path' => 'is_half_day', 'format' => 'boolean'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'start_date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'leaveType'],
                defaultSort: ['start_date', 'desc'],
            ),

            new ReportDefinition(
                key: 'paid-leave-report',
                section: 'leave_lop',
                title: 'Paid Leave Report',
                description: 'Approved leave requests under a paid leave type.',
                query: fn () => LeaveRequest::query()->where('status', 'approved')->whereHas('leaveType', fn ($q) => $q->where('is_paid', true)),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'leave_type', 'label' => 'Leave Type', 'path' => 'leaveType.name'],
                    ['key' => 'start_date', 'label' => 'From', 'path' => 'start_date', 'format' => 'date'],
                    ['key' => 'end_date', 'label' => 'To', 'path' => 'end_date', 'format' => 'date'],
                    ['key' => 'days', 'label' => 'Days', 'path' => 'total_days'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'start_date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'leaveType'],
                defaultSort: ['start_date', 'desc'],
            ),

            new ReportDefinition(
                key: 'unpaid-leave-report',
                section: 'leave_lop',
                title: 'Unpaid Leave Report',
                description: 'Approved leave requests under an unpaid leave type.',
                query: fn () => LeaveRequest::query()->where('status', 'approved')->whereHas('leaveType', fn ($q) => $q->where('is_paid', false)),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'leave_type', 'label' => 'Leave Type', 'path' => 'leaveType.name'],
                    ['key' => 'start_date', 'label' => 'From', 'path' => 'start_date', 'format' => 'date'],
                    ['key' => 'end_date', 'label' => 'To', 'path' => 'end_date', 'format' => 'date'],
                    ['key' => 'days', 'label' => 'Days', 'path' => 'total_days'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'start_date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'leaveType'],
                defaultSort: ['start_date', 'desc'],
            ),

            new ReportDefinition(
                key: 'lop-calculation-report',
                section: 'leave_lop',
                title: 'LOP Calculation Report',
                description: 'Full LOP breakdown per payroll record — absent days, unpaid leave, half-day LOP, late/early conversion, and the system-calculated total before any manual adjustment.',
                query: fn () => PayrollRecord::query(),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'absent_days', 'label' => 'Absent Days', 'path' => 'absent_days'],
                    ['key' => 'unpaid_leave', 'label' => 'Unpaid Leave', 'path' => 'unpaid_leave_days'],
                    ['key' => 'half_day_lop', 'label' => 'Half-Day LOP', 'path' => 'half_day_lop_days'],
                    ['key' => 'late_early_lop', 'label' => 'Late/Early Conversion', 'path' => 'late_early_lop_days'],
                    ['key' => 'calculated_lop', 'label' => 'Calculated LOP', 'path' => 'calculated_lop_days'],
                    ['key' => 'final_lop', 'label' => 'Final LOP', 'path' => 'lop_days'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'lop-adjustment-report',
                section: 'leave_lop',
                title: 'LOP Adjustment Report',
                description: 'Payroll records where the calculated LOP was manually overridden, with the reason and who confirmed it.',
                query: fn () => PayrollRecord::query()->whereNotNull('lop_override_reason'),
                columns: [
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'calculated_lop', 'label' => 'Calculated LOP', 'path' => 'calculated_lop_days'],
                    ['key' => 'final_lop', 'label' => 'Final LOP (Adjusted)', 'path' => 'lop_days'],
                    ['key' => 'reason', 'label' => 'Adjustment Reason', 'path' => 'lop_override_reason'],
                    ['key' => 'confirmed_by', 'label' => 'Approved By', 'path' => 'lopConfirmedBy.name'],
                    ['key' => 'confirmed_at', 'label' => 'Confirmed At', 'path' => 'lop_confirmed_at', 'format' => 'datetime'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'lopConfirmedBy'],
                defaultSort: ['employee_id', 'asc'],
            ),

            new ReportDefinition(
                key: 'branch-wise-lop-summary',
                section: 'leave_lop',
                title: 'Branch-Wise LOP Summary',
                description: 'Payroll records with LOP applied, sorted by branch.',
                query: fn () => PayrollRecord::query()->where('lop_days', '>', 0),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'lop_days', 'label' => 'LOP Days', 'path' => 'lop_days'],
                    ['key' => 'lop_deduction', 'label' => 'LOP Deduction', 'path' => 'lop_deduction', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['employee_id', 'asc'],
                summary: fn ($q) => [
                    ['label' => 'Total LOP Days', 'value' => number_format((clone $q)->sum('lop_days'), 2)],
                    ['label' => 'Total LOP Deduction', 'value' => number_format((clone $q)->sum('lop_deduction'), 2)],
                ],
            ),

            new ReportDefinition(
                key: 'contractor-wise-lop-summary',
                section: 'leave_lop',
                title: 'Contractor-Wise LOP Summary',
                description: 'Payroll records with LOP applied, for contract labour tracked as Employee rows, sorted by contractor. Independent Contract Workers are tracked separately (see Contractor LOP Report under Contractor Reports).',
                query: fn () => PayrollRecord::query()->where('lop_days', '>', 0)->whereHas('employee', fn ($q) => $q->whereNotNull('contractor_id')),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'employee.contractor.name'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'month', 'label' => 'Month', 'path' => 'month'],
                    ['key' => 'year', 'label' => 'Year', 'path' => 'year'],
                    ['key' => 'lop_days', 'label' => 'LOP Days', 'path' => 'lop_days'],
                    ['key' => 'lop_deduction', 'label' => 'LOP Deduction', 'path' => 'lop_deduction', 'format' => 'currency'],
                ],
                filterMap: $viaEmployee,
                periodFilter: true,
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'employee.contractor'],
                defaultSort: ['employee_id', 'asc'],
            ),
        ];
    }
}
