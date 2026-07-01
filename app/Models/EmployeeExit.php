<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeExit extends Model
{
    protected $table = 'employee_exits';
    protected $fillable = ['employee_id', 'exit_type', 'resignation_date', 'last_working_date', 'notice_period_days', 'exit_reason', 'remarks', 'status', 'approved_by'];
    protected function casts(): array { return ['resignation_date' => 'date', 'last_working_date' => 'date']; }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
