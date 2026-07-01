<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use SoftDeletes;

    protected $fillable = ['department_id', 'name', 'code', 'grade', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department() { return $this->belongsTo(Department::class); }
    public function employees()  { return $this->hasMany(Employee::class); }
}
