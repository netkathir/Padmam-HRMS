<?php

namespace App\Support;

/**
 * Module 11 (FSD 15.2) — "Sensitive employee, bank, payroll, PF, ESI, PAN,
 * and Aadhaar information is protected by a dedicated View Sensitive Data
 * permission." Single shared source of truth for both the masking rule and
 * the permission gate, used consistently across Employee profile, Payslip,
 * and Reports — replacing what used to be three different, inconsistent
 * mechanisms (a `reports`-only masking helper, a coarse `employees.full`
 * check gating bank-account unmasking, and PAN/UAN/PF/ESI on the employee
 * profile + payslip being unconditionally masked with no bypass at all, not
 * even for Super Admin).
 */
class SensitiveDataAccess
{
    public static function mask(?string $value): string
    {
        $value = (string) $value;

        return $value === '' ? '' : str_repeat('•', max(0, strlen($value) - 4)) . substr($value, -4);
    }

    /** Bypassed for non-branch-scoped/Super Admin accounts, exactly like every other BranchAdminPermissions check. */
    public static function canView(string $moduleKey): bool
    {
        if (! BranchScope::isBranchScopedUser()) {
            return true;
        }

        return BranchAdminPermissions::can(auth()->user(), $moduleKey, 'view_sensitive');
    }
}
