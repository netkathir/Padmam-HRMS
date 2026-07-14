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
        // Branch Administration fields, additive on this same table (single
        // source of truth — no separate Branch Administration branches table).
        'district', 'contact_person', 'branch_head_user_id', 'start_date',
        'created_by', 'updated_by',
        // Branch/Unit Master FSD — additive fields, same table.
        'unit_type', 'closure_date', 'pf_establishment_number', 'esi_employer_code', 'weekly_off_days',
        // Structured address entry on Create Branch, additive columns.
        'address_line1', 'address_line2',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'closure_date' => 'date',
            'weekly_off_days' => 'array',
        ];
    }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function manager()     { return $this->belongsTo(Employee::class, 'manager_id'); }
    public function departments() { return $this->hasMany(Department::class); }
    public function employees()   { return $this->hasMany(Employee::class); }
    public function holidays()    { return $this->hasMany(Holiday::class); }

    /**
     * This branch's effective Holiday Calendar (FSD 6.2) — every holiday
     * that applies here: global ones (holidays.branch_id IS NULL, per the
     * existing "NULL = all branches" convention) plus this branch's own.
     * Not a new "calendar" entity — the existing Holiday model's nullable
     * branch_id already implements exactly this, this is just a named,
     * convenient way to fetch it for one specific branch.
     */
    public function applicableHolidays()
    {
        return Holiday::where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $this->id));
    }

    // Branch Administration relations — additive, still the same Branch model.
    public function branchHead()  { return $this->belongsTo(User::class, 'branch_head_user_id'); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy()   { return $this->belongsTo(User::class, 'updated_by'); }
    public function users()             { return $this->hasMany(User::class, 'branch_id'); }
    public function headAssignments()   { return $this->hasMany(BranchHeadAssignment::class); }

    /**
     * Dashboard FSD multi-branch authorization grant — inverse of
     * User::branches(). Named distinctly from users() (the existing
     * hasMany via branch_id) since both relations coexist.
     */
    public function authorizedUsers()   { return $this->belongsToMany(User::class, 'user_branches'); }
}
