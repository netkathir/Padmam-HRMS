<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfEsiConfig extends Model
{
    protected $table = 'pf_esi_config';
    protected $fillable = [
        'pf_employee_percent', 'pf_employer_percent', 'pf_ceiling', 'pf_applies_to',
        'esi_employee_percent', 'esi_employer_percent', 'esi_ceiling', 'esi_applies_to',
        'effective_from', 'is_active'
    ];
    protected function casts(): array {
        return [
            'pf_employee_percent' => 'decimal:2', 'pf_employer_percent' => 'decimal:2', 'pf_ceiling' => 'decimal:2',
            'esi_employee_percent' => 'decimal:2', 'esi_employer_percent' => 'decimal:2', 'esi_ceiling' => 'decimal:2',
            'effective_from' => 'date', 'is_active' => 'boolean',
        ];
    }
}
