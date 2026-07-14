<?php

namespace App\Support\Reports\Definitions;

use App\Models\Attendance;
use App\Models\BiometricUpload;
use App\Support\Reports\ReportDefinition;

/**
 * FSD 14.3 — Attendance Reports. Present/Absent/Half-Day/Overtime/Monthly
 * Register are aliases onto existing reports (reports.attendance's `status`
 * filter, reports.overtime, and AttendanceController's own register); the
 * rest are new generic-engine definitions.
 */
class AttendanceReportDefinitions
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
                key: 'present-employee',
                section: 'attendance',
                title: 'Present Employee Report',
                description: 'Alias of the existing Attendance Report, filtered to present status.',
                aliasRoute: 'reports.attendance',
                aliasParams: ['status' => 'present'],
            ),
            new ReportDefinition(
                key: 'absent-employee',
                section: 'attendance',
                title: 'Absent Employee Report',
                description: 'Alias of the existing Attendance Report, filtered to absent status.',
                aliasRoute: 'reports.attendance',
                aliasParams: ['status' => 'absent'],
            ),
            new ReportDefinition(
                key: 'half-day-attendance',
                section: 'attendance',
                title: 'Half-Day Report',
                description: 'Alias of the existing Attendance Report, filtered to half-day status.',
                aliasRoute: 'reports.attendance',
                aliasParams: ['status' => 'half_day'],
            ),
            new ReportDefinition(
                key: 'overtime-report-attendance',
                section: 'attendance',
                title: 'Overtime Report',
                description: 'Alias of the existing Overtime Report.',
                aliasRoute: 'reports.overtime',
            ),
            new ReportDefinition(
                key: 'monthly-attendance-register',
                section: 'attendance',
                title: 'Monthly Attendance Register',
                description: 'Alias of the existing Attendance module register (Excel + PDF export already supported there).',
                aliasRoute: 'attendance.report',
            ),

            new ReportDefinition(
                key: 'daily-attendance-report',
                section: 'attendance',
                title: 'Daily Attendance Report',
                description: 'Attendance records for a specific date or date range, with full punch/status detail.',
                query: fn () => Attendance::query(),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'shift', 'label' => 'Shift', 'path' => 'shift.name'],
                    ['key' => 'in_time', 'label' => 'In Time', 'path' => 'in_time', 'format' => 'datetime'],
                    ['key' => 'out_time', 'label' => 'Out Time', 'path' => 'out_time', 'format' => 'datetime'],
                    ['key' => 'work_hours', 'label' => 'Total Hours', 'path' => 'work_hours', 'format' => 'number'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                    ['key' => 'late_minutes', 'label' => 'Late Minutes', 'path' => 'late_minutes'],
                    ['key' => 'early_minutes', 'label' => 'Early Exit Minutes', 'path' => 'early_exit_minutes'],
                    ['key' => 'ot_hours', 'label' => 'OT Hours', 'path' => 'ot_hours', 'format' => 'number'],
                    ['key' => 'source', 'label' => 'Source', 'path' => 'source_label'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                statusColumn: 'status',
                statusOptions: [
                    'present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day', 'late' => 'Late',
                    'early_exit' => 'Early Exit', 'on_leave' => 'On Leave', 'paid_leave' => 'Paid Leave',
                    'unpaid_leave' => 'Unpaid Leave', 'weekly_off' => 'Weekly Off', 'weekend' => 'Weekend',
                    'paid_holiday' => 'Paid Holiday', 'unpaid_holiday' => 'Unpaid Holiday', 'holiday' => 'Holiday',
                    'on_duty' => 'On Duty', 'missing_punch' => 'Missing Punch', 'pending_review' => 'Pending Review',
                ],
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'shift'],
                defaultSort: ['date', 'desc'],
                exportFormats: ['csv'],
            ),

            new ReportDefinition(
                key: 'late-entry-report',
                section: 'attendance',
                title: 'Late Entry Report',
                description: 'Attendance records flagged as a late entry.',
                query: fn () => Attendance::query()->where('is_late', true),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'in_time', 'label' => 'In Time', 'path' => 'in_time', 'format' => 'datetime'],
                    ['key' => 'late_minutes', 'label' => 'Late Minutes', 'path' => 'late_minutes'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'early-exit-report',
                section: 'attendance',
                title: 'Early Exit Report',
                description: 'Attendance records flagged as an early exit.',
                query: fn () => Attendance::query()->where('is_early_exit', true),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'out_time', 'label' => 'Out Time', 'path' => 'out_time', 'format' => 'datetime'],
                    ['key' => 'early_minutes', 'label' => 'Early Exit Minutes', 'path' => 'early_exit_minutes'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'missing-punch-report',
                section: 'attendance',
                title: 'Missing Punch Report',
                description: 'Attendance records with a missing punch, not yet resolved.',
                query: fn () => Attendance::query()->where('status', 'missing_punch'),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'in_time', 'label' => 'In Time', 'path' => 'in_time', 'format' => 'datetime'],
                    ['key' => 'out_time', 'label' => 'Out Time', 'path' => 'out_time', 'format' => 'datetime'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'weekly-off-report',
                section: 'attendance',
                title: 'Weekly Off Report',
                description: 'Attendance records marked as a weekly off day.',
                query: fn () => Attendance::query()->whereIn('status', ['weekend', 'weekly_off']),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'holiday-attendance-report',
                section: 'attendance',
                title: 'Holiday Attendance Report',
                description: 'Attendance records marked as a holiday (paid or unpaid).',
                query: fn () => Attendance::query()->whereIn('status', ['holiday', 'paid_holiday', 'unpaid_holiday']),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'biometric-upload-summary',
                section: 'attendance',
                title: 'Biometric Upload Summary',
                description: 'Batch-level summary of biometric attendance uploads (row counts, validation status). Row-level rejection reasons are only available in each batch\'s downloadable error file, not as a queryable table.',
                query: fn () => BiometricUpload::query(),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'period_from', 'label' => 'Period From', 'path' => 'period_from', 'format' => 'date'],
                    ['key' => 'period_to', 'label' => 'Period To', 'path' => 'period_to', 'format' => 'date'],
                    ['key' => 'file', 'label' => 'File', 'path' => 'original_filename'],
                    ['key' => 'total', 'label' => 'Total Rows', 'path' => 'total_rows'],
                    ['key' => 'valid', 'label' => 'Valid Rows', 'path' => 'valid_rows'],
                    ['key' => 'invalid', 'label' => 'Invalid Rows', 'path' => 'invalid_rows'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status'],
                    ['key' => 'uploader', 'label' => 'Uploaded By', 'path' => 'uploader.name'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                dateColumn: 'period_from',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch', 'uploader'],
                defaultSort: ['period_from', 'desc'],
            ),

            new ReportDefinition(
                key: 'invalid-biometric-data-report',
                section: 'attendance',
                title: 'Invalid Biometric Data Report',
                description: 'Biometric upload batches that contained invalid, duplicate, or unresolved rows (batch-level counts only — see the batch\'s error file for row-level detail).',
                query: fn () => BiometricUpload::query()->where(fn ($q) => $q
                    ->where('invalid_rows', '>', 0)
                    ->orWhere('duplicate_rows', '>', 0)
                    ->orWhere('unknown_employee_rows', '>', 0)
                    ->orWhere('invalid_date_rows', '>', 0)
                    ->orWhere('invalid_time_rows', '>', 0)),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'branch.name'],
                    ['key' => 'period_from', 'label' => 'Period From', 'path' => 'period_from', 'format' => 'date'],
                    ['key' => 'file', 'label' => 'File', 'path' => 'original_filename'],
                    ['key' => 'invalid', 'label' => 'Invalid Rows', 'path' => 'invalid_rows'],
                    ['key' => 'duplicate', 'label' => 'Duplicate Rows', 'path' => 'duplicate_rows'],
                    ['key' => 'unknown_employee', 'label' => 'Unknown Employee Rows', 'path' => 'unknown_employee_rows'],
                    ['key' => 'invalid_date', 'label' => 'Invalid Date Rows', 'path' => 'invalid_date_rows'],
                    ['key' => 'invalid_time', 'label' => 'Invalid Time Rows', 'path' => 'invalid_time_rows'],
                ],
                filterMap: ['branch_id' => 'branch_id'],
                dateColumn: 'period_from',
                branchScope: ['type' => 'direct'],
                eagerLoads: ['branch'],
                defaultSort: ['period_from', 'desc'],
            ),

            new ReportDefinition(
                key: 'attendance-correction-report',
                section: 'attendance',
                title: 'Attendance Correction Report',
                description: 'Attendance records manually corrected, with the recorded reason and any supporting document.',
                query: fn () => Attendance::query()->where('source', 'corrected'),
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'reason', 'label' => 'Correction Reason', 'path' => 'correction_reason'],
                    ['key' => 'document', 'label' => 'Supporting Document', 'path' => 'supporting_document_path'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'contractor-wise-attendance-report',
                section: 'attendance',
                title: 'Contractor-Wise Attendance Report',
                description: 'Company-recorded contract labour attendance (Employee-sourced), grouped by contractor. Independent Contract Workers tracked outside the Employee table have their own report — see Contractor Attendance Report under Contractor Reports.',
                query: fn () => Attendance::query()->whereHas('employee', fn ($q) => $q->whereNotNull('contractor_id')),
                columns: [
                    ['key' => 'contractor', 'label' => 'Contractor', 'path' => 'employee.contractor.name'],
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                    ['key' => 'work_hours', 'label' => 'Total Hours', 'path' => 'work_hours', 'format' => 'number'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch', 'employee.contractor'],
                defaultSort: ['date', 'desc'],
            ),

            new ReportDefinition(
                key: 'branch-wise-attendance-summary',
                section: 'attendance',
                title: 'Branch-Wise Attendance Summary',
                description: 'All attendance records, sorted by branch.',
                query: fn () => Attendance::query(),
                columns: [
                    ['key' => 'branch', 'label' => 'Branch', 'path' => 'employee.branch.name'],
                    ['key' => 'date', 'label' => 'Date', 'path' => 'date', 'format' => 'date'],
                    ['key' => 'code', 'label' => 'Employee No.', 'path' => 'employee.employee_code'],
                    ['key' => 'name', 'label' => 'Name', 'path' => 'employee.full_name'],
                    ['key' => 'status', 'label' => 'Status', 'path' => 'status_label'],
                ],
                filterMap: $viaEmployee,
                dateColumn: 'date',
                branchScope: ['type' => 'via', 'relation' => 'employee'],
                eagerLoads: ['employee.branch'],
                defaultSort: ['date', 'desc'],
                summary: fn ($q) => [
                    ['label' => 'Total Records', 'value' => (clone $q)->count()],
                ],
            ),
        ];
    }
}
