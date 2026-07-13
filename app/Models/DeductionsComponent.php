<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionsComponent extends Model
{
    protected $table = 'deductions_components';
    protected $fillable = [
        'name', 'code', 'type', 'calculation_base', 'percentage',
        'is_statutory', 'sort_order', 'is_active',
    ];
    protected function casts(): array {
        return [
            'percentage' => 'decimal:2',
            'is_statutory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
    public function salarySlabComponents() { return $this->hasMany(SalarySlabComponent::class, 'component_id')->where('component_type', 'deduction'); }
}
