<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $table = 'attendance_logs';
    protected $fillable = ['attendance_id', 'employee_id', 'punch_time', 'punch_type', 'source', 'device_id', 'location'];
    protected function casts(): array { return ['punch_time' => 'datetime']; }
    public function attendance() { return $this->belongsTo(Attendance::class); }
    public function employee()   { return $this->belongsTo(Employee::class); }
}
