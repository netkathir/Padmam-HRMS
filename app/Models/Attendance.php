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
        'leave_type_id',
        'lop_days',
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
        'correction_reason',
        'supporting_document_path',
        'ot_approval_status',
        'ot_approved_by',
        'ot_approved_at',
        'biometric_upload_id',
        'applied_rules',
    ];
    protected function casts(): array {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
            'ot_approved_at' => 'datetime',
            'is_late' => 'boolean',
            'is_early_exit' => 'boolean',
            'is_manual_entry' => 'boolean',
            'lop_days' => 'decimal:2',
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
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function otApprover()
    {
        return $this->belongsTo(User::class, 'ot_approved_by');
    }
    public function biometricUpload()
    {
        return $this->belongsTo(BiometricUpload::class);
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

    /** FSD 11.4 "In Time: First valid punch" / "Out Time: Last valid punch" — aliases for report/export consumers. */
    public function getFirstInAttribute()
    {
        return $this->in_time;
    }

    public function getLastOutAttribute()
    {
        return $this->out_time;
    }

    /** FSD 11.4 "Attendance Source: Biometric, Manual or Corrected" */
    public function getSourceLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->source));
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
