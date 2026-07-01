<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    protected $table = 'employee_salary_structure';
    protected $fillable = [
        'employee_id', 'salary_slab_id', 'ctc',
        'basic_salary', 'hra', 'da', 'ta', 'medical_allowance', 'special_allowance', 'gross_salary',
        'pf_employee', 'pf_employer', 'esi_employee', 'esi_employer', 'tds', 'net_salary',
        'effective_from', 'effective_to', 'is_current', 'created_by',
    ];

    protected function casts(): array {
        return [
            'ctc'               => 'decimal:2',
            'basic_salary'      => 'decimal:2',
            'hra'               => 'decimal:2',
            'da'                => 'decimal:2',
            'ta'                => 'decimal:2',
            'medical_allowance' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'gross_salary'      => 'decimal:2',
            'pf_employee'       => 'decimal:2',
            'pf_employer'       => 'decimal:2',
            'esi_employee'      => 'decimal:2',
            'esi_employer'      => 'decimal:2',
            'tds'               => 'decimal:2',
            'net_salary'        => 'decimal:2',
            'effective_from'    => 'date',
            'effective_to'      => 'date',
            'is_current'        => 'boolean',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function slab()     { return $this->belongsTo(SalarySlab::class, 'salary_slab_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}
