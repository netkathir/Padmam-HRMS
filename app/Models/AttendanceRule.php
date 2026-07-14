<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRule extends Model
{
    protected $fillable = [
        'rule_id', 'shift_ids', 'min_full_day_hours', 'min_half_day_hours',
        'late_grace_minutes', 'early_exit_grace_minutes', 'missing_punch_treatment',
        'single_punch_treatment', 'multiple_punch_handling', 'weekly_off_treatment',
        'holiday_treatment', 'work_on_holiday_treatment', 'work_on_weekly_off_treatment',
        'consecutive_absence_rule', 'rounding_minutes',
    ];

    protected function casts(): array
    {
        return [
            'shift_ids' => 'array',
            'min_full_day_hours' => 'decimal:2',
            'min_half_day_hours' => 'decimal:2',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }

    public function appliesToShift(?int $shiftId): bool
    {
        return empty($this->shift_ids) || in_array($shiftId, $this->shift_ids, false);
    }
}
