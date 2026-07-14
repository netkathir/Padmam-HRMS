<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsiRule extends Model
{
    protected $table = 'esi_rules';

    protected $fillable = [
        'rule_id', 'esi_applicable', 'salary_slab_from', 'salary_slab_to',
        'esi_wage_components', 'employee_esi_percentage', 'employer_esi_percentage', 'rounding_method',
    ];

    protected function casts(): array
    {
        return [
            'esi_applicable' => 'boolean',
            'salary_slab_from' => 'decimal:2',
            'salary_slab_to' => 'decimal:2',
            'esi_wage_components' => 'array',
            'employee_esi_percentage' => 'decimal:2',
            'employer_esi_percentage' => 'decimal:2',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
