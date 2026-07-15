<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessRule extends Model
{
    protected $table = 'rules';

    use SoftDeletes;

    public const CATEGORIES = [
        'employee_number', 'attendance', 'weekly_off', 'holiday',
        'lop', 'pf', 'esi', 'tds', 'payroll', 'overtime',
    ];

    /** Category => detail relation method name, for categories with a detail table. */
    public const DETAIL_RELATIONS = [
        'employee_number' => 'employeeNumberRule',
        'attendance'       => 'attendanceRule',
        'weekly_off'       => 'weeklyOffRule',
        'lop'              => 'lopRule',
        'pf'               => 'pfRule',
        'esi'              => 'esiRule',
        'tds'              => 'tdsRule',
        'overtime'         => 'overtimeRule',
    ];

    protected $fillable = [
        'name', 'category', 'branch_ids', 'employee_types', 'labour_types',
        'contractor_ids', 'priority', 'effective_from', 'effective_to',
        'status', 'description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'branch_ids' => 'array',
            'employee_types' => 'array',
            'labour_types' => 'array',
            'contractor_ids' => 'array',
            'priority' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employeeNumberRule() { return $this->hasOne(EmployeeNumberRule::class, 'rule_id'); }
    public function attendanceRule()     { return $this->hasOne(AttendanceRule::class, 'rule_id'); }
    public function weeklyOffRule()      { return $this->hasOne(WeeklyOffRule::class, 'rule_id'); }
    public function lopRule()            { return $this->hasOne(LopRule::class, 'rule_id'); }
    public function pfRule()             { return $this->hasOne(PfRule::class, 'rule_id'); }
    public function esiRule()            { return $this->hasOne(EsiRule::class, 'rule_id'); }
    public function tdsRule()            { return $this->hasOne(TdsRule::class, 'rule_id'); }
    public function overtimeRule()       { return $this->hasOne(OvertimeRule::class, 'rule_id'); }

    public function detail(): ?Model
    {
        $relation = self::DETAIL_RELATIONS[$this->category] ?? null;
        return $relation ? $this->{$relation} : null;
    }

    /**
     * Whether this rule's applicability matches the given context. Empty/null
     * applicability arrays are wildcards (match everything for that axis) —
     * same "NULL = applies to all" convention used throughout Module 3.
     */
    public function matchesApplicability(?int $branchId, ?string $primaryType, ?string $labourType, ?int $contractorId): bool
    {
        if (! empty($this->branch_ids) && ! in_array($branchId, $this->branch_ids, false)) {
            return false;
        }
        if (! empty($this->employee_types) && $primaryType && ! in_array($primaryType, $this->employee_types, true)) {
            return false;
        }
        if ($primaryType === 'labour' && ! empty($this->labour_types) && $labourType && ! in_array($labourType, $this->labour_types, true)) {
            return false;
        }
        if (! empty($this->contractor_ids) && ! in_array($contractorId, $this->contractor_ids, false)) {
            return false;
        }

        return true;
    }

    /** Higher = more specific. Used to break ties between same-priority rules. */
    public function specificity(): int
    {
        return (! empty($this->contractor_ids) ? 2 : 0)
            + (! empty($this->branch_ids) ? 1 : 0)
            + (! empty($this->employee_types) ? 1 : 0);
    }

    /**
     * FSD 8.1/8.2 — resolve the single winning rule for a category given an
     * employee's context. Mirrors PfEsiConfig::effectiveOn() / SalarySlab::findApplicable()'s
     * "effective on this date" style, extended with applicability matching
     * and priority + specificity ordering ("more specific rules shall
     * override general rules" — suggested order: contractor-specific >
     * branch+employee-type > branch > org default).
     */
    public static function resolveFor(
        string $category,
        ?int $branchId,
        ?string $primaryType,
        ?string $labourType = null,
        ?int $contractorId = null,
        ?string $date = null
    ): ?self {
        $date = $date ?? now()->toDateString();
        $relation = self::DETAIL_RELATIONS[$category] ?? null;

        $candidates = static::query()
            ->when($relation, fn($q) => $q->with($relation))
            ->where('category', $category)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->get()
            ->filter(fn(self $rule) => $rule->matchesApplicability($branchId, $primaryType, $labourType, $contractorId));

        return $candidates
            ->sortBy([
                fn($a, $b) => $a->priority <=> $b->priority,
                fn($a, $b) => $b->specificity() <=> $a->specificity(),
            ])
            ->first();
    }

    /**
     * FSD 10.3.3 — "Weekly Off Rule, Attendance Rule, Payroll Rule
     * references" — per-employee overrides of the Module 4 resolution.
     * `weekly_off_rule_id`/`attendance_rule_id` map 1:1 to their category;
     * `payroll_rule_id` is a single reference field covering every other
     * payroll category (lop/pf/esi/tds/overtime), so it only takes effect
     * when its own category matches the one being resolved for. An
     * employee with no override set (the common case) resolves exactly as
     * `resolveFor()` always has — zero behavior change.
     */
    public static function resolveForEmployee(
        Employee $employee,
        string $category,
        ?int $branchId,
        ?string $primaryType,
        ?string $labourType = null,
        ?int $contractorId = null,
        ?string $date = null
    ): ?self {
        $overrideId = match ($category) {
            'weekly_off'  => $employee->weekly_off_rule_id,
            'attendance'  => $employee->attendance_rule_id,
            default       => $employee->payroll_rule_id,
        };

        if ($overrideId) {
            $override = static::with(self::DETAIL_RELATIONS[$category] ?? [])
                ->where('id', $overrideId)
                ->where('category', $category)
                ->where('status', 'active')
                ->first();
            if ($override) {
                return $override;
            }
        }

        return static::resolveFor($category, $branchId, $primaryType, $labourType, $contractorId, $date);
    }
}
