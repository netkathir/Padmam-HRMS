<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRequest extends Model
{
    protected $table = 'permission_requests';
    protected $fillable = [
        'employee_id', 'date', 'from_time', 'to_time', 'duration_minutes',
        'reason', 'status', 'approved_by', 'approved_at', 'rejection_reason'
    ];
    protected function casts(): array { return ['date' => 'date', 'approved_at' => 'datetime']; }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
