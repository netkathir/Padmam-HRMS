<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionsComponent extends Model
{
    protected $table = 'deductions_components';
    protected $fillable = ['name', 'code', 'type', 'is_tax_deduction', 'is_active'];
    protected function casts(): array { return ['is_tax_deduction' => 'boolean', 'is_active' => 'boolean']; }
    public function salarySlabComponents() { return $this->hasMany(SalarySlabComponent::class, 'component_id')->where('component_type', 'deduction'); }
}
