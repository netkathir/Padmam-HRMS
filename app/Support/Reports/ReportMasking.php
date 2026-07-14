<?php

namespace App\Support\Reports;

use App\Support\SensitiveDataAccess;

/**
 * FSD 14.2 — "Sensitive fields shall be masked based on user permission."
 * Thin, backward-compatible wrapper over the shared App\Support\
 * SensitiveDataAccess helper (Module 11 — the same masking rule/gate is now
 * also used by the Employee profile and Payslip screens, not just Reports).
 * Kept as its own class so every existing `ReportMasking::` call site in the
 * Reports module needs no changes.
 */
class ReportMasking
{
    public static function mask(?string $value): string
    {
        return SensitiveDataAccess::mask($value);
    }

    public static function canViewSensitive(): bool
    {
        return SensitiveDataAccess::canView('reports');
    }
}
