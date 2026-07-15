<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlabComponent extends Model
{
    protected $table = 'salary_slab_components';

    protected $fillable = [
        'salary_slab_id', 'component_type', 'component_id', 'component_name',
        'calculation_type', 'calculation_base', 'rate', 'calculated_amount',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'calculated_amount' => 'decimal:2',
        ];
    }

    public function salarySlab()
    {
        return $this->belongsTo(SalarySlab::class, 'salary_slab_id');
    }
}
