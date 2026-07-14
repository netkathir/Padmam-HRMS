<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $table = 'payroll_records';
    protected $fillable = [
        'employee_id', 'month', 'year',
        'working_days', 'pro_rated_days', 'present_days', 'absent_days', 'leave_days', 'lop_days', 'holiday_days', 'ot_hours',
        'basic_salary', 'hra', 'da', 'ta', 'medical_allowance', 'special_allowance',
        'ot_amount', 'other_earnings', 'gross_earnings',
        'pf_employee', 'esi_employee', 'tds', 'advance_deduction', 'lop_deduction',
        'other_deductions', 'total_deductions',
        'pf_employer', 'esi_employer', 'employer_cost',
        'net_salary', 'status', 'generated_by', 'generated_at',
        'applied_rules', 'calculated_lop_days', 'lop_override_reason',
        'unpaid_leave_days', 'half_day_lop_days', 'late_early_lop_days',
        'lop_applied', 'lop_confirmed_at', 'lop_confirmed_by',
        'confirmed_by', 'confirmed_at', 'closed_by', 'closed_at',
        'reopened_by', 'reopened_at', 'reopen_reason',
    ];

    /** FSD 13.6 — statuses that still allow recalculation/data changes (Draft + Calculated, per this system's mapping). */
    public const EDITABLE_STATUSES = ['draft', 'calculated'];

    protected function casts(): array {
        return [
            'present_days'      => 'decimal:2',
            'absent_days'       => 'decimal:2',
            'leave_days'        => 'decimal:2',
            'lop_days'          => 'decimal:2',
            'pro_rated_days'    => 'decimal:2',
            'calculated_lop_days' => 'decimal:2',
            'unpaid_leave_days'   => 'decimal:2',
            'half_day_lop_days'   => 'decimal:2',
            'late_early_lop_days' => 'decimal:2',
            'lop_applied'         => 'boolean',
            'lop_confirmed_at'    => 'datetime',
            'confirmed_at'        => 'datetime',
            'closed_at'           => 'datetime',
            'reopened_at'         => 'datetime',
            'ot_hours'          => 'decimal:2',
            'basic_salary'      => 'decimal:2',
            'hra'               => 'decimal:2',
            'da'                => 'decimal:2',
            'ta'                => 'decimal:2',
            'medical_allowance' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'ot_amount'         => 'decimal:2',
            'other_earnings'    => 'decimal:2',
            'gross_earnings'    => 'decimal:2',
            'pf_employee'       => 'decimal:2',
            'esi_employee'      => 'decimal:2',
            'tds'               => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'lop_deduction'     => 'decimal:2',
            'other_deductions'  => 'decimal:2',
            'total_deductions'  => 'decimal:2',
            'pf_employer'       => 'decimal:2',
            'esi_employer'      => 'decimal:2',
            'employer_cost'     => 'decimal:2',
            'net_salary'        => 'decimal:2',
            'generated_at'      => 'datetime',
            'applied_rules'     => 'array',
        ];
    }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function generator()   { return $this->belongsTo(User::class, 'generated_by'); }
    public function approver()    { return $this->belongsTo(User::class, 'approved_by'); }
    public function lopConfirmedBy() { return $this->belongsTo(User::class, 'lop_confirmed_by'); }
    public function confirmedBy() { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function closedBy()    { return $this->belongsTo(User::class, 'closed_by'); }
    public function reopenedBy()  { return $this->belongsTo(User::class, 'reopened_by'); }
    public function allowances()  { return $this->hasMany(PayrollAllowance::class, 'payroll_id'); }
    public function deductions()  { return $this->hasMany(PayrollDeduction::class, 'payroll_id'); }
    public function payments()    { return $this->hasMany(PayrollPayment::class, 'payroll_id'); }

    /** FSD 13.2 — "Payroll Status: Draft, Calculated, Confirmed, Closed" display label. */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft', 'calculated' => 'Calculated',
            'confirmed' => 'Confirmed',
            'closed' => 'Closed',
            'paid' => 'Paid',
            default => ucfirst($this->status),
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    /** FSD 13.4 "Paid Days: Calculated" — eligible (pro-rated) days minus approved LOP. */
    public function getPaidDaysAttribute(): float
    {
        $eligible = $this->pro_rated_days ?? $this->working_days;
        return max(0, round((float) $eligible - (float) ($this->lop_applied ? $this->lop_days : 0), 2));
    }
}
