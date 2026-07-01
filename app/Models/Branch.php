<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'address', 'city', 'state', 'pincode',
        'phone', 'email', 'manager_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function manager()     { return $this->belongsTo(Employee::class, 'manager_id'); }
    public function departments() { return $this->hasMany(Department::class); }
    public function employees()   { return $this->hasMany(Employee::class); }
    public function holidays()    { return $this->hasMany(Holiday::class); }
}
