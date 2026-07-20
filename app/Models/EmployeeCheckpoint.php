<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCheckpoint extends Model
{
    protected $fillable = ['checkpoint_id', 'emp_checkpoint_id', 'employee_id'];

    public function checkpoint() { return $this->belongsTo(Checkpoint::class); }
    public function employee()  { return $this->belongsTo(Employee::class); }

    public function masterViewFields(): array
    {
        return [
            'Checkpoint'          => $this->checkpoint->name ?? '—',
            'Employee Checkpoint ID' => $this->emp_checkpoint_id,
            'Employee'            => $this->employee->full_name ?? '—',
            'Employee Code'       => $this->employee->employee_code ?? '—',
        ];
    }
}
