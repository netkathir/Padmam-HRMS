<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** FSD 11.2 — biometric Excel upload batch header + validation summary. */
class BiometricUpload extends Model
{
    protected $table = 'biometric_uploads';
    protected $fillable = [
        'branch_id', 'period_from', 'period_to', 'file_path', 'original_filename', 'sheet_name',
        'column_mapping', 'remarks', 'uploaded_by', 'total_rows', 'valid_rows', 'invalid_rows',
        'duplicate_rows', 'updated_rows', 'unknown_employee_rows', 'invalid_date_rows', 'invalid_time_rows',
        'wrong_branch_rows', 'error_file_path', 'status',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'column_mapping' => 'array',
        ];
    }

    public function branch()   { return $this->belongsTo(Branch::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function logs()     { return $this->hasMany(AttendanceLog::class); }
    public function attendanceRecords() { return $this->hasMany(Attendance::class); }
}
