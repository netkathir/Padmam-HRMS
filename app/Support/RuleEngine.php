<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Module 4 Rule Engine — shared calculation helpers used by PF/ESI/TDS rule
 * resolution (rounding) and Employee Number rule resolution (financial-year
 * labelling). Kept as static helpers (not a model) since nothing here is
 * persisted — it's pure calculation shared across PayrollController and
 * EmployeeNumberGenerator.
 */
class RuleEngine
{
    /**
     * Round a monetary amount to the nearest whole rupee per the rule's
     * configured Rounding Method (Nearest/Up/Down) — the standard convention
     * for PF/ESI/TDS statutory rounding in Indian payroll.
     */
    public static function roundAmount(float $value, ?string $method): float
    {
        return match ($method) {
            'up'   => ceil($value),
            'down' => floor($value),
            default => round($value),
        };
    }

    /**
     * Round a minute duration to the nearest configured increment (e.g. 15,
     * 30, 60). Zero/null means no rounding.
     */
    public static function roundMinutes(int $minutes, ?int $incrementMinutes): int
    {
        if (! $incrementMinutes || $incrementMinutes <= 0) {
            return $minutes;
        }

        return (int) (round($minutes / $incrementMinutes) * $incrementMinutes);
    }

    /**
     * "2026-27"-style financial-year label for a date, given the FY start
     * month (1-12, from CompanyProfile::financial_year_start) — the first
     * real consumer of that field, which was previously captured but never
     * used anywhere in the codebase.
     */
    public static function financialYearLabel(Carbon $date, int $fyStartMonth = 4): string
    {
        if ($date->month >= $fyStartMonth) {
            $startYear = $date->year;
        } else {
            $startYear = $date->year - 1;
        }

        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }
}
