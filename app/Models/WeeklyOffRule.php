<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyOffRule extends Model
{
    protected $fillable = ['rule_id', 'weekly_off_days', 'is_paid', 'min_attendance_condition'];

    protected function casts(): array
    {
        return [
            'weekly_off_days' => 'array',
            'is_paid' => 'boolean',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
