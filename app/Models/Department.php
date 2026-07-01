<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'code', 'head_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function branch()       { return $this->belongsTo(Branch::class); }
    public function head()         { return $this->belongsTo(Employee::class, 'head_id'); }
    public function designations() { return $this->hasMany(Designation::class); }
    public function employees()    { return $this->hasMany(Employee::class); }
}
