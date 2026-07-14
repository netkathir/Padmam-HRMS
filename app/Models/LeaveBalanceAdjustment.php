<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** FSD 12.3 — append-only audit trail for manual leave balance adjustments. */
class LeaveBalanceAdjustment extends Model
{
    const UPDATED_AT = null;

    protected $table = 'leave_balance_adjustments';
    protected $fillable = ['leave_balance_id', 'employee_id', 'leave_type_id', 'adjustment_days', 'reason', 'adjusted_by'];

    protected function casts(): array
    {
        return ['adjustment_days' => 'decimal:2'];
    }

    public function leaveBalance() { return $this->belongsTo(LeaveBalance::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function leaveType()   { return $this->belongsTo(LeaveType::class); }
    public function adjustedBy()  { return $this->belongsTo(User::class, 'adjusted_by'); }
}
