<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlab extends Model
{
    protected $table = 'salary_slabs';
    protected $fillable = ['name', 'min_ctc', 'max_ctc', 'is_active'];
    protected function casts(): array { return ['min_ctc' => 'decimal:2', 'max_ctc' => 'decimal:2', 'is_active' => 'boolean']; }
    public function components() { return $this->hasMany(SalarySlabComponent::class, 'salary_slab_id'); }
    public function salaryStructures() { return $this->hasMany(EmployeeSalaryStructure::class, 'salary_slab_id'); }
    public function employees() { return $this->hasMany(Employee::class, 'salary_slab_id'); }
}
