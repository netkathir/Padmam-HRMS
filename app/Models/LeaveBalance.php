<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';
    protected $fillable = [
        'employee_id', 'leave_type_id', 'year', 'opening_balance', 'allocated_days',
        'carry_forward_days', 'adjusted_days', 'used_days', 'pending_days', 'lapsed_days',
    ];
    protected function casts(): array {
        return [
            'opening_balance' => 'decimal:1', 'allocated_days' => 'decimal:1', 'carry_forward_days' => 'decimal:1',
            'adjusted_days' => 'decimal:1', 'used_days' => 'decimal:1', 'pending_days' => 'decimal:1', 'lapsed_days' => 'decimal:1',
        ];
    }

    public function employee()    { return $this->belongsTo(Employee::class); }
    public function leaveType()   { return $this->belongsTo(LeaveType::class); }
    public function adjustments() { return $this->hasMany(LeaveBalanceAdjustment::class)->latest(); }

    /**
     * FSD 12.3 "Available Balance" — Opening + Accrued (allocated) + Carry
     * Forward + Adjusted - Used - Pending - Lapsed. `pending_days` isn't an
     * FSD-listed field but is kept in the formula (pre-existing behavior):
     * a pending request must still reduce what's available to request
     * further, or double-booking would be possible before approval.
     */
    public function getBalanceDaysAttribute(): float
    {
        return (float) $this->opening_balance + (float) $this->allocated_days + (float) $this->carry_forward_days
            + (float) $this->adjusted_days - (float) $this->used_days - (float) $this->pending_days - (float) $this->lapsed_days;
    }

    /** FSD-labeled alias for balance_days — same value, clearer name for new views. */
    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance_days;
    }
}
