<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'leave_types';
    protected $fillable = [
        'name', 'code', 'days_per_year', 'max_carry_forward', 'is_carry_forward',
        'is_paid', 'is_half_day_allowed', 'gender_specific', 'requires_document',
        'min_notice_days', 'max_consecutive_days', 'is_active',
    ];
    protected function casts(): array {
        return [
            'is_carry_forward' => 'boolean',
            'is_paid' => 'boolean',
            'is_half_day_allowed' => 'boolean',
            'requires_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
    public function balances() { return $this->hasMany(LeaveBalance::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
}
