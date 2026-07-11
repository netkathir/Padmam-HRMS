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

        return $user->role?->permissions()
            ->wherePivot($column, true)
            ->where('module', $moduleKey)
            ->exists() ?? false;
    }

    /**
     * "Manage Users" isn't tied to a single module — granted if the user's
     * role has it set on any of its module permission grants.
     */
    public static function canManageUsers(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->role?->permissions()
            ->wherePivot('can_manage_users', true)
            ->exists() ?? false;
    }
}
