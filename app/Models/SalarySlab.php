<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlab extends Model
{
    protected $table = 'salary_slabs';
    protected $fillable = ['name', 'code', 'min_salary', 'max_salary', 'is_active'];
    protected function casts(): array { return ['min_salary' => 'decimal:2', 'max_salary' => 'decimal:2', 'is_active' => 'boolean']; }
    public function components() { return $this->hasMany(SalarySlabComponent::class, 'slab_id'); }
    public function salaryStructures() { return $this->hasMany(EmployeeSalaryStructure::class, 'slab_id'); }
}
