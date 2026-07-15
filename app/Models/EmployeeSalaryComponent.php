<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    protected $table = 'employee_salary_components';

    protected $fillable = [
        'employee_salary_structure_id', 'component_type', 'component_id', 'component_name',
        'calculation_type', 'calculation_base', 'rate', 'calculated_amount',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'calculated_amount' => 'decimal:2',
        ];
    }

    public function salaryStructure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'employee_salary_structure_id');
    }
}
