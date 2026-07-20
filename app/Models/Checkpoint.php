<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkpoint extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function employeeCheckpoints() { return $this->hasMany(EmployeeCheckpoint::class); }

    public function masterViewFields(): array
    {
        return [
            'Branch'      => $this->branch->name ?? '—',
            'Name'        => $this->name,
            'Code'        => $this->code,
            'Description' => $this->description ?? '—',
            'Status'      => $this->is_active ? 'Active' : 'Inactive',
        ];
    }
}
