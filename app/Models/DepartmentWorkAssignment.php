<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentWorkAssignment extends Model
{
    protected $fillable = ['employee_id', 'department_id', 'work_date', 'value_per_day', 'created_by'];

    protected function casts(): array
    {
        return [
            'work_date'     => 'date',
            'value_per_day' => 'decimal:2',
        ];
    }

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function department(){ return $this->belongsTo(Department::class); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
}
