<?php

namespace App\Support;

use App\Models\User;

/**
 * Branch Administration — fine-grained action-level permission checks
 * (Approve/Process/Export Excel/Export PDF/View Sensitive Data/Manage Users)
 * backed by the existing `role_permissions` pivot table (extended with these
 * boolean columns) — NOT a separate permission table. A role's grant for a
 * given module (whichever access_level was assigned via the existing Role
 * Permissions screen) carries these extra flags.
 *
 * This is a SUPPLEMENTARY layer on top of the existing Read/Create/Full
 * Gate system — it never replaces the `permission:{module}.read` middleware
 * that still gates whether a user can reach a screen at all. It is consulted
 * explicitly, action-by-action, from specific controller methods, and only
 * ever for branch-scoped accounts (see BranchScope::isBranchScopedUser()) —
 * callers must not invoke this for legacy/unscoped accounts.
 */
class BranchAdminPermissions
{
    public static function can(?User $user, string $moduleKey, string $action): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $column = "can_$action";

        // Module 11 multi-role — granted if ANY of the user's assigned roles
        // (union, not just the primary role_id) carries this flag for this
        // module. A single-role user sees no behavior change.
        return $user->allRoles()->contains(fn ($role) => $role->permissions()
            ->wherePivot($column, true)
            ->where('module', $moduleKey)
            ->exists());
    }

    /**
     * "Manage Users" isn't tied to a single module — granted if ANY of the
     * user's assigned roles has it set on any of its module permission grants.
     */
    public static function canManageUsers(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->allRoles()->contains(fn ($role) => $role->permissions()
            ->wherePivot('can_manage_users', true)
            ->exists());
    }
}
