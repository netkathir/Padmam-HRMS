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
        'status',
        'work_minutes',
        'late_minutes',
        'early_exit_minutes',
        'ot_minutes',
        'is_manual',
        'manual_reason',
        'approved_by',
        'remarks'
    ];
    protected function casts(): array
    {
        return ['date' => 'date', 'is_manual' => 'boolean'];
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
