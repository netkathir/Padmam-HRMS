<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name', 'display_name', 'description', 'is_active',
        // Branch Administration — additive fields (Role Code, Applicable User
        // Type, Created By) on the existing roles table; no separate table.
        'role_code', 'applicable_user_types', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'applicable_user_types' => 'array'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot([
                'can_approve', 'can_process', 'can_export_excel', 'can_export_pdf', 'can_view_sensitive', 'can_manage_users',
                'can_confirm', 'can_close', 'can_reopen', 'can_recalculate', 'can_modify_rules', 'can_modify_payroll',
                'can_view_audit_log', 'can_delete',
            ]);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
