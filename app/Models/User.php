<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'username', 'password',
        'role_id', 'employee_id', 'avatar', 'is_active', 'last_login_at', 'last_login_ip',
        // Branch Administration module — additive fields, unused by the existing
        // System Admin > Users screen.
        'user_type', 'branch_id', 'mobile', 'force_password_change', 'account_expiry_date',
        'is_locked', 'created_by', 'updated_by', 'remarks',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'               => 'hashed',
            'is_active'               => 'boolean',
            'last_login_at'           => 'datetime',
            'force_password_change'   => 'boolean',
            'is_locked'                => 'boolean',
            'account_expiry_date'      => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Branch Administration relations/helpers — additive fields on this
    //    same users table, powering both this screen and the branch-scoped
    //    workflows (Branch Head Assignment, Branch Switcher, BranchScope). ──
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isBranchScoped(): bool
    {
        return ! $this->isSuperAdmin()
            && in_array($this->user_type, ['branch_head', 'branch_user'], true)
            && $this->branch_id !== null;
    }

    /**
     * Dashboard FSD — "Branch multi-select (authorized users only)". Additive
     * many-to-many authorization grant for roles other than Super Admin
     * (unrestricted, all branches) and Branch Head/Branch User (single
     * branch via branch_id above, unchanged) — e.g. HR Administrator,
     * Payroll Administrator. Consumed by BranchScope::authorizedBranchIds().
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branches');
    }

    // ── Helpers ────────────────────────────────────────────────────
    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role?->name, ['super_admin', 'admin', 'hr']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    /**
     * Check whether this user's role has been granted the given permission name.
     * super_admin always returns true (Gate::before also enforces this).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        // Eloquent lazily-loads and caches role->permissions per request
        return $this->role?->permissions->contains('name', $permission) ?? false;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : asset('images/default-avatar.png');
    }
}
