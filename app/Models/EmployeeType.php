<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    protected $table = 'employee_types';
    protected $fillable = ['name', 'code', 'description', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function employees() { return $this->hasMany(Employee::class); }
}
