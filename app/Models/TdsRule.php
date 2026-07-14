<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsRule extends Model
{
    protected $table = 'tds_rules';

    protected $fillable = [
        'rule_id', 'tds_applicable', 'salary_slab_from', 'salary_slab_to', 'tds_percentage',
        'calculation_basis', 'taxable_components', 'exempt_components',
        'fixed_tds_amount_allowed', 'rounding_method',
    ];

    protected function casts(): array
    {
        return [
            'tds_applicable' => 'boolean',
            'salary_slab_from' => 'decimal:2',
            'salary_slab_to' => 'decimal:2',
            'tds_percentage' => 'decimal:2',
            'taxable_components' => 'array',
            'exempt_components' => 'array',
            'fixed_tds_amount_allowed' => 'boolean',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
