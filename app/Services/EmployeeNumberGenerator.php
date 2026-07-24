<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CompanyProfile;
use App\Models\Contractor;
use App\Models\BusinessRule;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\RuleSequenceCounter;
use App\Support\RuleEngine;
use App\Support\SequentialCodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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

    /**
     * Decides the Employee Code to use for a new/updated employee: the
     * applicable Employee Number Rule's generated value (unless manual
     * override is both permitted and provided), or — when no rule is
     * configured — the Employee-Type-prefixed default sequence. Shared by
     * EmployeeController (employee creation and Employee Slab save) and the
     * employees:backfill-codes command, so every caller follows the exact
     * same resolution order.
     */
    public function resolveEmployeeCode(array $data, ?string $primaryType, ?string $labourType): ?string
    {
        $rule = $this->resolveRule($primaryType, $labourType, $data['branch_id'] ?? null, $data['contractor_id'] ?? null);

        if ($rule) {
            $detail = $rule->employeeNumberRule;
            $canManuallyOverride = $detail->allow_manual_override && auth()->check() && auth()->user()->can('rule_engine.full');
            if (! $canManuallyOverride || empty($data['employee_code'])) {
                return $this->generate($rule, $data['branch_id'] ?? null, $data['contractor_id'] ?? null);
            }
            return $data['employee_code'] ?? null;
        }

        return ($data['employee_code'] ?? null) ?: $this->generateDefaultCode($data['employee_type_id'] ?? null, $data['branch_id'] ?? null);
    }

    /**
     * Default Employee Code generator used when no Employee Number Rule is
     * configured — prefixed with the first two letters of the selected
     * Employee Type's name (e.g. "Permanent" -> "PE0001", "PE0002"...; a
     * generic "EMP" prefix when even the Employee Type isn't known yet),
     * restarting from 0001 in EACH branch — branch_id scopes the "last
     * code" lookup, matching the composite unique index on (branch_id,
     * employee_code): branch A's 5th employee and branch B's 1st employee
     * must both be able to be "0001" under the same prefix. A row lock on
     * the latest matching employee (within this branch) serializes
     * concurrent creations; the retry loop is a defensive fallback against
     * the rare duplicate-key race the lock doesn't cover.
     */
    public function generateDefaultCode(?int $employeeTypeId, ?int $branchId = null): string
    {
        $prefix = $this->typePrefix($employeeTypeId);

        // withTrashed() is load-bearing here, same as
        // ShiftController::createWithGeneratedCode(): a soft-deleted
        // Employee's code is still permanently reserved by the database's
        // unique index, so excluding deleted rows can compute a code
        // that's already taken. Also uses the row with the HIGHEST code
        // NUMBER under this prefix, not just "whichever row has the
        // highest id" — those can drift apart (e.g. a deleted row created
        // after the newest surviving one).
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                return DB::transaction(function () use ($prefix, $branchId) {
                    $allCodes = Employee::withTrashed()
                        ->where('employee_code', 'like', $prefix . '%')
                        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                        ->lockForUpdate()
                        ->pluck('employee_code');
                    $lastCode = SequentialCodeGenerator::highestCode($allCodes);

                    return SequentialCodeGenerator::next($lastCode, $prefix . '0001');
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 10) {
                    throw $e;
                }
                usleep(random_int(20_000, 80_000));
            }
        }

        throw new \RuntimeException('Unable to generate a unique Employee Code after several attempts.');
    }

    /** First two letters of the selected Employee Type's own name, uppercased — or "EMP" when no type is known yet. */
    public function typePrefix(?int $employeeTypeId): string
    {
        $name = $employeeTypeId ? EmployeeType::find($employeeTypeId)?->name : null;

        return $name ? strtoupper(substr($name, 0, 2)) : 'EMP';
    }
}
