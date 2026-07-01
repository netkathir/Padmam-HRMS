<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlabComponent extends Model
{
    protected $table = 'salary_slab_components';
    protected $fillable = ['slab_id', 'component_type', 'component_id', 'calculation_type', 'value', 'sequence'];
    protected function casts(): array { return ['value' => 'decimal:4']; }
    public function slab() { return $this->belongsTo(SalarySlab::class, 'slab_id'); }
}
