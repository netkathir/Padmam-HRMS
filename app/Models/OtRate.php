<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtRate extends Model
{
    protected $table = 'ot_rates';
    protected $fillable = ['name', 'rate_multiplier', 'applicable_for', 'min_hours', 'is_active'];
    protected function casts(): array { return ['rate_multiplier' => 'decimal:2', 'min_hours' => 'decimal:2', 'is_active' => 'boolean']; }
}
