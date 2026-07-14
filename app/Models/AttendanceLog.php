<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'attendance_logs';
    protected $fillable = [
        'attendance_id', 'employee_id', 'employee_code', 'device_id', 'punch_time', 'punch_type',
        'source', 'latitude', 'longitude', 'is_processed', 'biometric_upload_id', 'raw_data',
    ];
    protected function casts(): array
    {
        return [
            'punch_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_processed' => 'boolean',
            'raw_data' => 'array',
        ];
    }
    public function attendance()      { return $this->belongsTo(Attendance::class); }
    public function employee()        { return $this->belongsTo(Employee::class); }
    public function biometricUpload() { return $this->belongsTo(BiometricUpload::class); }
}
