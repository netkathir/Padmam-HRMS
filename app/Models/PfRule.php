<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfRule extends Model
{
    protected $table = 'pf_rules';

    protected $fillable = [
        'rule_id', 'pf_applicable', 'salary_slab_from', 'salary_slab_to',
        'pf_wage_components', 'employee_pf_percentage', 'employer_pf_percentage',
        'pf_wage_ceiling', 'restrict_to_wage_ceiling', 'voluntary_pf_allowed', 'rounding_method',
    ];

    protected function casts(): array
    {
        return [
            'pf_applicable' => 'boolean',
            'salary_slab_from' => 'decimal:2',
            'salary_slab_to' => 'decimal:2',
            'pf_wage_components' => 'array',
            'employee_pf_percentage' => 'decimal:2',
            'employer_pf_percentage' => 'decimal:2',
            'pf_wage_ceiling' => 'decimal:2',
            'restrict_to_wage_ceiling' => 'boolean',
            'voluntary_pf_allowed' => 'boolean',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
