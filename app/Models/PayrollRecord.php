<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $table = 'payroll_records';
    protected $fillable = [
        'employee_id', 'month', 'year',
        'working_days', 'present_days', 'absent_days', 'leave_days', 'lop_days', 'holiday_days', 'ot_hours',
        'basic_salary', 'hra', 'da', 'ta', 'medical_allowance', 'special_allowance',
        'ot_amount', 'other_earnings', 'gross_earnings',
        'pf_employee', 'esi_employee', 'tds', 'advance_deduction', 'lop_deduction',
        'other_deductions', 'total_deductions',
        'pf_employer', 'esi_employer',
        'net_salary', 'status', 'generated_by', 'generated_at',
        'applied_rules', 'calculated_lop_days', 'lop_override_reason',
        'unpaid_leave_days', 'half_day_lop_days', 'late_early_lop_days',
        'lop_applied', 'lop_confirmed_at', 'lop_confirmed_by',
    ];

    protected function casts(): array {
        return [
            'present_days'      => 'decimal:2',
            'absent_days'       => 'decimal:2',
            'leave_days'        => 'decimal:2',
            'lop_days'          => 'decimal:2',
            'calculated_lop_days' => 'decimal:2',
            'unpaid_leave_days'   => 'decimal:2',
            'half_day_lop_days'   => 'decimal:2',
            'late_early_lop_days' => 'decimal:2',
            'lop_applied'         => 'boolean',
            'lop_confirmed_at'    => 'datetime',
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
            'net_salary'        => 'decimal:2',
            'generated_at'      => 'datetime',
            'applied_rules'     => 'array',
        ];
    }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function generator()   { return $this->belongsTo(User::class, 'generated_by'); }
    public function approver()    { return $this->belongsTo(User::class, 'approved_by'); }
    public function lopConfirmedBy() { return $this->belongsTo(User::class, 'lop_confirmed_by'); }
    public function allowances()  { return $this->hasMany(PayrollAllowance::class, 'payroll_id'); }
    public function deductions()  { return $this->hasMany(PayrollDeduction::class, 'payroll_id'); }
    public function payments()    { return $this->hasMany(PayrollPayment::class, 'payroll_id'); }
}
