<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlabComponent extends Model
{
    protected $table = 'salary_slab_components';
    public $timestamps = false;
    protected $fillable = ['salary_slab_id', 'component_type', 'component_id', 'value_type', 'value'];
    protected function casts(): array { return ['value' => 'decimal:2']; }
    public function slab() { return $this->belongsTo(SalarySlab::class, 'salary_slab_id'); }
}
