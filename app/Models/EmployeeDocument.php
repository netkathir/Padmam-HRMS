<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';
    protected $fillable = [
        'employee_id', 'document_type', 'document_name', 'document_number', 'file_path',
        'file_size', 'file_type', 'issue_date', 'expiry_date', 'is_verified', 'remarks', 'uploaded_by',
    ];
    protected function casts(): array { return ['issue_date' => 'date', 'expiry_date' => 'date', 'is_verified' => 'boolean']; }
    public function employee() { return $this->belongsTo(Employee::class); }
}
