<?php

namespace App\Support\Reports;

use App\Support\BranchScope;
use Illuminate\Http\Request;

/**
 * FSD 14.1 — applies the standard filter vocabulary (Branch, Employee Type,
 * Labour Type, Contractor, Department, Employee, Date Range/Payroll Month)
 * to any report's base query, driven entirely by the ReportDefinition — a
 * straight generalization of the `->when($request->filled(...), ...)` chains
 * already hand-written in every one of the 9 existing ReportController
 * methods, just table-driven instead of repeated per report.
 */
class ReportFilterApplier
{
    private const STANDARD_KEYS = ['branch_id', 'department_id', 'employee_type_id', 'labour_type', 'contractor_id', 'employee_id'];

    public static function apply($query, ReportDefinition $definition, Request $request)
    {
        $query = match ($definition->branchScope['type'] ?? 'none') {
            'direct' => BranchScope::scopeQuery($query, $definition->branchScope['column'] ?? 'branch_id'),
            'via'    => BranchScope::scopeQueryVia($query, $definition->branchScope['relation'], $definition->branchScope['column'] ?? 'branch_id'),
            default  => $query,
        };

        foreach (self::STANDARD_KEYS as $key) {
            if (! isset($definition->filterMap[$key]) || ! $request->filled($key)) {
                continue;
            }
            self::applyPath($query, $definition->filterMap[$key], $request->input($key));
        }

        if ($definition->dateColumn) {
            $from = $request->input('from_date');
            $to   = $request->input('to_date');

            if ($from && $to) {
                $query->whereBetween($definition->dateColumn, [$from, $to]);
            } elseif ($from) {
                $query->where($definition->dateColumn, '>=', $from);
            } elseif ($to) {
                $query->where($definition->dateColumn, '<=', $to);
            }
        }

        if ($definition->periodFilter) {
            if ($request->filled('month')) {
                $query->where('month', $request->input('month'));
            }
            if ($request->filled('year')) {
                $query->where('year', $request->input('year'));
            }
        }

        if ($definition->statusColumn && $request->filled('status')) {
            self::applyPath($query, $definition->statusColumn, $request->input('status'));
        }

        return $query;
    }

    /**
     * Splits on the LAST dot (not the first) so a two-hop path like
     * 'payroll.employee.department_id' resolves to relation
     * 'payroll.employee' (Laravel's whereHas() natively supports dotted
     * nested-relation paths) + column 'department_id', while a one-hop path
     * like 'employee.branch_id' still resolves exactly as before.
     */
    private static function applyPath($query, string $path, $value): void
    {
        $lastDot = strrpos($path, '.');

        if ($lastDot === false) {
            $query->where($path, $value);

            return;
        }

        $relation = substr($path, 0, $lastDot);
        $column   = substr($path, $lastDot + 1);
        $query->whereHas($relation, fn ($q) => $q->where($column, $value));
    }
}
