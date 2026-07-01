<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShiftAssignment extends Model
{
    protected $table = 'employee_shift_assignments';
    protected $fillable = ['employee_id', 'shift_id', 'effective_from', 'effective_to', 'assigned_by'];
    protected function casts(): array { return ['effective_from' => 'date', 'effective_to' => 'date']; }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function shift()    { return $this->belongsTo(Shift::class); }
    public function assigner() { return $this->belongsTo(User::class, 'assigned_by'); }
}
