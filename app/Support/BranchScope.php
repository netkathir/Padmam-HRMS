<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Branch;

/**
 * Branch Administration — resolves "the effective branch" for the current
 * request and applies it consistently across existing controllers.
 *
 * Backward-compatibility guardrail: currentBranchId() returns null for every
 * account that predates this module (user_type is null), which makes every
 * method here a no-op for those accounts — behavior is unchanged for them.
 *
 * The application must always have a specific branch in effect for a Super
 * Admin — there is no "All Branches" mode. See resolveSuperAdminBranchId().
 */
class BranchScope
{
    public static function isBranchScopedUser(): bool
    {
        $user = auth()->user();

        // A Super Admin is NEVER branch-scoped, no matter what user_type/
        // branch_id happen to be set on their row (e.g. residual data from
        // a past Head Assignment). This is the concrete guardrail for "Super
        // Admin must always have unrestricted access... regardless of any
        // assigned role or permission."
        return $user
            && ! $user->isSuperAdmin()
            && in_array($user->user_type, ['branch_head', 'branch_user'], true)
            && $user->branch_id !== null;
    }

    /**
     * Branch-scoped user (branch_head/branch_user) → always their own branch,
     * no override possible. Super Admin → always a real, active branch (see
     * resolveSuperAdminBranchId() — never null, there is no "All Branches"
     * mode). Anyone else (legacy accounts, user_type null) → always null,
     * preserving today's unscoped behavior for accounts that predate this
     * module.
     */
    public static function currentBranchId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (self::isBranchScopedUser()) {
            return $user->branch_id;
        }

        if ($user->isSuperAdmin()) {
            return self::resolveSuperAdminBranchId();
        }

        return null;
    }

    /**
     * The Branch model behind currentBranchId(), for screens that need to
     * display the active branch (read-only) instead of a selectable dropdown
     * — e.g. Create/Edit forms for branch-scoped Masters.
     */
    public static function currentBranch(): ?Branch
    {
        $id = self::currentBranchId();

        return $id ? Branch::find($id) : null;
    }

    /**
     * A Super Admin always has exactly one branch in effect. Uses the
     * session-selected branch (set via the Branch Switcher) as long as it's
     * still a real, active branch; otherwise falls back to — and persists —
     * the first active branch (lowest id), covering a fresh login, a never-
     * switched session, or a previously-selected branch that has since been
     * deactivated/deleted.
     */
    private static function resolveSuperAdminBranchId(): ?int
    {
        $sessionBranchId = session('current_branch_id');

        if ($sessionBranchId && Branch::active()->whereKey($sessionBranchId)->exists()) {
            return (int) $sessionBranchId;
        }

        $fallbackBranchId = Branch::active()->orderBy('id')->value('id');

        if ($fallbackBranchId) {
            session(['current_branch_id' => $fallbackBranchId]);
        }

        return $fallbackBranchId;
    }

    /**
     * Dashboard FSD — "Branch multi-select (authorized users only)". The
     * full set of branch ids a user may ever see/select, distinct from
     * currentBranchId() (which is exactly one "currently effective" branch
     * used everywhere else in the app). Only consumed by the Overall/Branch
     * Dashboard controllers — every other module keeps using
     * currentBranchId()/scopeQuery() unchanged.
     *
     * Super Admin → every active branch (unrestricted, unchanged).
     * Branch-scoped user (branch_head/branch_user) → their own single
     * branch (unchanged). Anyone else → whatever branches have been
     * explicitly granted via the user_branches pivot (User::branches()) —
     * empty by default, meaning no dashboard access until granted.
     */
    public static function authorizedBranchIds(): \Illuminate\Support\Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        if ($user->isSuperAdmin()) {
            return Branch::active()->orderBy('id')->pluck('id');
        }

        if (self::isBranchScopedUser()) {
            return collect([$user->branch_id]);
        }

        return $user->branches()->orderBy('branches.id')->pluck('branches.id');
    }

    /**
     * Add a `where($column, currentBranchId)` clause when a branch is in
     * effect. No-op (returns $query untouched) otherwise.
     */
    public static function scopeQuery($query, string $column = 'branch_id')
    {
        $branchId = self::currentBranchId();

        return $branchId ? $query->where($column, $branchId) : $query;
    }

    /**
     * Same as scopeQuery(), but for models that only reach branch_id through
     * a relation (e.g. Attendance/Leave/Payroll via employee.branch_id).
     */
    public static function scopeQueryVia($query, string $relation, string $column = 'branch_id')
    {
        $branchId = self::currentBranchId();

        return $branchId
            ? $query->whereHas($relation, fn($q) => $q->where($column, $branchId))
            : $query;
    }

    /**
     * Like scopeQuery(), but for models where a NULL branch_id deliberately
     * means "applies to every branch" (e.g. a national Holiday), not "not yet
     * assigned" — includes rows with a NULL column value alongside the exact
     * match, instead of excluding them.
     */
    public static function scopeQueryIncludingGlobal($query, string $column = 'branch_id')
    {
        $branchId = self::currentBranchId();

        return $branchId
            ? $query->where(fn($q) => $q->whereNull($column)->orWhere($column, $branchId))
            : $query;
    }

    /**
     * Abort 403 (and audit-log the attempt) if the current effective branch
     * doesn't match the record being accessed. No-op when no branch is in
     * effect (null currentBranchId — e.g. legacy accounts, unscoped Super Admin).
     */
    public static function assertBranchAccess(?int $recordBranchId): void
    {
        $branchId = self::currentBranchId();

        if ($branchId !== null && $recordBranchId !== $branchId) {
            AuditLog::write(auth()->id(), 'unauthorized_branch_access', 'branch_scope', $recordBranchId ?? '', null, null, $branchId);
            abort(403, 'You do not have access to this branch\'s data.');
        }
    }

    /**
     * Force branch_id to the current effective branch on write (ignoring/
     * overwriting anything client-supplied) whenever one is in effect —
     * covers both a branch-scoped user (always their own branch) and a
     * Super Admin who has switched to a specific branch via the Branch
     * Switcher ("all new records shall be created under the currently
     * selected Branch ID"). No-op when unscoped (no branch in effect) —
     * an unscoped Super Admin's form keeps carrying an explicit branch_id.
     */
    public static function stampBranchId(array $data): array
    {
        $branchId = self::currentBranchId();

        if ($branchId !== null) {
            $data['branch_id'] = $branchId;
        }

        return $data;
    }

    /**
     * Abort if the given branch is inactive — an inactive branch must not
     * accept new operational transactions (spec requirement), though its
     * historical data stays visible/untouched. No-op for a null branch id
     * (nothing to check) so this is safe to call unconditionally after
     * stampBranchId()/normal branch_id resolution.
     */
    public static function assertBranchIsActive(?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        $branch = Branch::find($branchId);

        if ($branch && ! $branch->is_active) {
            abort(422, "The branch \"{$branch->name}\" is inactive and cannot accept new transactions.");
        }
    }
}
