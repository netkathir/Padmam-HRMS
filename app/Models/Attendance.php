<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'shift_id',
        'in_time',
        'out_time',
        'work_minutes',
        'ot_minutes',
        'status',
        'is_late',
        'late_minutes',
        'is_early_exit',
        'early_exit_minutes',
        'source',
        'is_manual_entry',
        'approval_status',
        'approved_by',
        'approved_at',
        'remarks',
        'applied_rules',
    ];
    protected function casts(): array {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
            'is_late' => 'boolean',
            'is_early_exit' => 'boolean',
            'is_manual_entry' => 'boolean',
            'applied_rules' => 'array',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function getWorkHoursAttribute(): float
    {
        return round($this->work_minutes / 60, 2);
    }

    public function getOtHoursAttribute(): float
    {
        return round($this->ot_minutes / 60, 2);
    }

    public function getLateHoursAttribute(): float
    {
        return round($this->late_minutes / 60, 2);
    }
}
