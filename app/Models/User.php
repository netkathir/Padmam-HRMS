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

    /**
     * Module 11 (FSD 15.1) — "Role: Multi-select, mandatory, at least one
     * role." Additive alongside the singular `role_id`/`role()` above, which
     * is kept in sync as the "primary" role (first selected) so any legacy
     * code reading `$user->role` directly keeps working unchanged.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * The full set of roles this user should be evaluated against for every
     * permission check — falls back to the singular `role` if `roles` is
     * ever empty (defensive: should not happen after the role_user backfill,
     * but keeps a user with no role_user row from silently losing access).
     */
    public function allRoles()
    {
        $roles = $this->roles;

        return $roles->isNotEmpty() ? $roles : collect($this->role ? [$this->role] : []);
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
    /** Checks across ALL assigned roles (Module 11 multi-role), not just the primary one. */
    public function hasRole(string $role): bool
    {
        return $this->allRoles()->contains(fn ($r) => $r->name === $role);
    }

    /** True if Super Admin is any of this user's assigned roles, not just the primary one. */
    public function isSuperAdmin(): bool
    {
        return $this->allRoles()->contains(fn ($r) => $r->name === 'super_admin');
    }

    /**
     * Check whether ANY of this user's assigned roles has been granted the
     * given permission name (union across roles — Module 11 multi-role).
     * super_admin always returns true (Gate::before also enforces this).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->allRoles()->contains(fn ($r) => $r->permissions->contains('name', $permission));
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : asset('images/default-avatar.png');
    }
}
