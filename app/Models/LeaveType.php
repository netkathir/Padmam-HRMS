<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'leave_types';
    protected $fillable = [
        'name', 'code', 'days_allowed', 'carry_forward', 'max_carry_forward',
        'gender_specific', 'requires_document', 'min_notice_days', 'max_consecutive_days',
        'is_paid', 'is_active'
    ];
    protected function casts(): array {
        return [
            'carry_forward' => 'boolean', 'requires_document' => 'boolean',
            'is_paid' => 'boolean', 'is_active' => 'boolean',
        ];
    }
    public function balances() { return $this->hasMany(LeaveBalance::class); }
    public function requests() { return $this->hasMany(LeaveRequest::class); }
}
