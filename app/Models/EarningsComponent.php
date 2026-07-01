<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarningsComponent extends Model
{
    protected $table = 'earnings_components';
    protected $fillable = ['name', 'code', 'type', 'is_taxable', 'is_pf_applicable', 'is_esi_applicable', 'is_active'];
    protected function casts(): array {
        return ['is_taxable' => 'boolean', 'is_pf_applicable' => 'boolean', 'is_esi_applicable' => 'boolean', 'is_active' => 'boolean'];
    }
    public function salarySlabComponents() { return $this->hasMany(SalarySlabComponent::class, 'component_id')->where('component_type', 'earning'); }
}
