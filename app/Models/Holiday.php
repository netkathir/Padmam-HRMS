<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'type', 'branch_id', 'description', 'is_active'];
    protected function casts(): array { return ['date' => 'date', 'is_active' => 'boolean']; }
    public function branch() { return $this->belongsTo(Branch::class); }
}
