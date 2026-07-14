<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CompanyProfile;
use App\Models\Contractor;
use App\Models\BusinessRule;
use App\Models\RuleSequenceCounter;
use App\Support\RuleEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Module 4 FSD 8.3 — Employee Number Rule. Resolves the applicable Employee
 * Number Rule (if any) for a new hire and generates their employee code.
 * Callers must treat a null return as "no rule configured" and fall back to
 * today's manual-entry behavior (EmployeeController does this) — this keeps
 * every existing deployment's employee creation flow unchanged until an
 * Employee Number Rule is actually configured.
 */
class EmployeeNumberGenerator
{
    /**
     * Resolve the applicable rule for this employee's classification, or
     * null if none is configured — the caller decides what "no rule" means.
     */
    public function resolveRule(?string $primaryType, ?string $labourType, ?int $branchId, ?int $contractorId, ?Carbon $date = null): ?BusinessRule
    {
        $date = $date ?? now();
        $dateStr = $date->toDateString();
        $employeeCategory = $primaryType === 'staff' ? 'staff' : $labourType;

        if (! $employeeCategory) {
            return null;
        }

        $candidates = BusinessRule::query()
            ->with('employeeNumberRule')
            ->where('category', 'employee_number')
            ->where('status', 'active')
            ->where('effective_from', '<=', $dateStr)
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $dateStr))
            ->get()
            ->filter(fn(BusinessRule $r) => $r->matchesApplicability($branchId, $primaryType, $labourType, $contractorId))
            ->filter(function (BusinessRule $r) use ($employeeCategory, $branchId, $contractorId) {
                $detail = $r->employeeNumberRule;
                if (! $detail || $detail->employee_category !== $employeeCategory) {
                    return false;
                }
                if ($detail->branch_id && (int) $detail->branch_id !== (int) $branchId) {
                    return false;
                }
                if ($detail->contractor_id && (int) $detail->contractor_id !== (int) $contractorId) {
                    return false;
                }
                return true;
            });

        return $candidates
            ->sortBy([
                fn($a, $b) => $a->priority <=> $b->priority,
                fn($a, $b) => $b->specificity() <=> $a->specificity(),
            ])
            ->first();
    }

    /**
     * Generate the next employee number for the given rule. Uses a
     * transaction + row lock on the sequence counter so concurrent employee
     * creations never hand out the same number, and the counter only ever
     * increases — deleting/inactivating an employee never frees their
     * sequence number for reuse (FSD 8.3 business rule).
     */
    public function generate(BusinessRule $rule, ?int $branchId, ?int $contractorId, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $detail = $rule->employeeNumberRule;

        $scopeKey = match ($detail->reset_frequency) {
            'yearly' => 'CY' . $date->year,
            'financial_yearly' => 'FY' . RuleEngine::financialYearLabel($date, (int) (CompanyProfile::first()?->financial_year_start ?? 4)),
            'branch_wise' => 'branch:' . ($branchId ?? 'none'),
            default => 'global',
        };

        $sequence = DB::transaction(function () use ($rule, $detail, $scopeKey) {
            $counter = RuleSequenceCounter::where('rule_id', $rule->id)
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = RuleSequenceCounter::create([
                    'rule_id' => $rule->id,
                    'scope_key' => $scopeKey,
                    'last_sequence' => $detail->sequence_start - 1,
                ]);
            }

            $next = max($counter->last_sequence + 1, $detail->sequence_start);
            $counter->update(['last_sequence' => $next]);

            return $next;
        });

        $parts = [];
        if ($detail->prefix) {
            $parts[] = $detail->prefix;
        }
        if ($detail->include_branch_code && $branchId) {
            $parts[] = Branch::find($branchId)?->code;
        }
        if ($detail->include_contractor_code && $contractorId) {
            $parts[] = Contractor::find($contractorId)?->code;
        }
        if ($detail->include_financial_year) {
            $parts[] = RuleEngine::financialYearLabel($date, (int) (CompanyProfile::first()?->financial_year_start ?? 4));
        } elseif ($detail->include_calendar_year) {
            $parts[] = (string) $date->year;
        }
        $parts[] = str_pad((string) $sequence, $detail->sequence_length, '0', STR_PAD_LEFT);

        return implode($detail->separator ?: '-', array_filter($parts, fn($p) => $p !== null && $p !== ''));
    }

    /**
     * Preview-only variant for the Rule Engine's "Sample Employee Number"
     * field — computes what the NEXT number would look like without
     * consuming a sequence value.
     */
    public function preview(BusinessRule $rule, ?int $branchId, ?int $contractorId, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $detail = $rule->employeeNumberRule;

        $scopeKey = match ($detail->reset_frequency) {
            'yearly' => 'CY' . $date->year,
            'financial_yearly' => 'FY' . RuleEngine::financialYearLabel($date, (int) (CompanyProfile::first()?->financial_year_start ?? 4)),
            'branch_wise' => 'branch:' . ($branchId ?? 'none'),
            default => 'global',
        };

        $lastSequence = RuleSequenceCounter::where('rule_id', $rule->id)->where('scope_key', $scopeKey)->value('last_sequence');
        $sequence = max(($lastSequence ?? ($detail->sequence_start - 1)) + 1, $detail->sequence_start);

        $parts = [];
        if ($detail->prefix) $parts[] = $detail->prefix;
        if ($detail->include_branch_code) $parts[] = $branchId ? Branch::find($branchId)?->code : 'BR';
        if ($detail->include_contractor_code) $parts[] = $contractorId ? Contractor::find($contractorId)?->code : 'CTR';
        if ($detail->include_financial_year) {
            $parts[] = RuleEngine::financialYearLabel($date, (int) (CompanyProfile::first()?->financial_year_start ?? 4));
        } elseif ($detail->include_calendar_year) {
            $parts[] = (string) $date->year;
        }
        $parts[] = str_pad((string) $sequence, $detail->sequence_length, '0', STR_PAD_LEFT);

        return implode($detail->separator ?: '-', array_filter($parts, fn($p) => $p !== null && $p !== ''));
    }
}
