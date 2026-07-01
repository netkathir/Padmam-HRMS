<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';
    protected $fillable = ['employee_id', 'document_type', 'document_number', 'file_path', 'expiry_date', 'is_verified'];
    protected function casts(): array { return ['expiry_date' => 'date', 'is_verified' => 'boolean']; }
    public function employee() { return $this->belongsTo(Employee::class); }
}
