<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LopRule extends Model
{
    protected $table = 'lop_rules';

    protected $fillable = [
        'rule_id', 'calculation_basis', 'fixed_payroll_days', 'half_day_lop_value',
        'full_day_lop_value', 'unpaid_leave_as_lop', 'absent_day_as_lop',
        'missing_punch_as_lop', 'late_count_conversion', 'early_exit_conversion',
        'holiday_between_absences', 'weekly_off_between_absences', 'manual_lop_adjustment_allowed',
    ];

    protected function casts(): array
    {
        return [
            'half_day_lop_value' => 'decimal:2',
            'full_day_lop_value' => 'decimal:2',
            'unpaid_leave_as_lop' => 'boolean',
            'absent_day_as_lop' => 'boolean',
            'missing_punch_as_lop' => 'boolean',
            'holiday_between_absences' => 'boolean',
            'weekly_off_between_absences' => 'boolean',
            'manual_lop_adjustment_allowed' => 'boolean',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
