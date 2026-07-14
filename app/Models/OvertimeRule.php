<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRule extends Model
{
    protected $fillable = [
        'rule_id', 'overtime_applicable', 'minimum_overtime_minutes', 'overtime_calculation',
        'overtime_rate', 'overtime_rounding_minutes', 'maximum_overtime_per_day_minutes',
        'approval_required', 'weekly_off_overtime_rate', 'holiday_overtime_rate',
    ];

    protected function casts(): array
    {
        return [
            'overtime_applicable' => 'boolean',
            'overtime_rate' => 'decimal:2',
            'approval_required' => 'boolean',
            'weekly_off_overtime_rate' => 'decimal:2',
            'holiday_overtime_rate' => 'decimal:2',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
