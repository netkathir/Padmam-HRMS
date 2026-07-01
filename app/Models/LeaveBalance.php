<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';
    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'allocated_days', 'carry_forward_days', 'used_days', 'pending_days', 'lapsed_days'];
    protected function casts(): array {
        return [
            'allocated_days' => 'decimal:1', 'carry_forward_days' => 'decimal:1',
            'used_days' => 'decimal:1', 'pending_days' => 'decimal:1', 'lapsed_days' => 'decimal:1',
        ];
    }

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }

    // Computed column from schema: balance_days = allocated_days + carry_forward_days - used_days - pending_days
    public function getBalanceDaysAttribute(): float
    {
        return (float)$this->allocated_days + (float)$this->carry_forward_days
            - (float)$this->used_days - (float)$this->pending_days;
    }
}
