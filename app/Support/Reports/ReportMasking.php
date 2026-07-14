<?php

namespace App\Support\Reports;

use App\Support\BranchAdminPermissions;
use App\Support\BranchScope;

/**
 * FSD 14.2 — "Sensitive fields shall be masked based on user permission."
 * Single source of truth for the masking rule (bullets + last 4 characters,
 * identical to the one existing masking accessor in the app,
 * EmployeeBankDetail::getMaskedAccountNumberAttribute()) and for the
 * permission gate (mirrors BranchAdminPermissions' existing bypass: never
 * enforced for non-branch-scoped/Super Admin accounts).
 */
class ReportMasking
{
    public static function mask(?string $value): string
    {
        $value = (string) $value;

        return $value === '' ? '' : str_repeat('•', max(0, strlen($value) - 4)) . substr($value, -4);
    }

    public static function canViewSensitive(): bool
    {
        if (! BranchScope::isBranchScopedUser()) {
            return true;
        }

        return BranchAdminPermissions::can(auth()->user(), 'reports', 'view_sensitive');
    }
}
