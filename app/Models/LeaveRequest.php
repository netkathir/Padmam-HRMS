<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $table = 'leave_requests';
    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days',
        'is_half_day', 'half_day_period', 'reason', 'document_path', 'status',
        'applied_by', 'approved_by', 'approved_at', 'rejection_reason',
        'cancelled_by', 'cancelled_at',
    ];
    protected function casts(): array {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'is_half_day'  => 'boolean',
            'approved_at'  => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
