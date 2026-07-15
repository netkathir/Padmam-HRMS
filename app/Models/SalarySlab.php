<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySlab extends Model
{
    use SoftDeletes;

    protected $table = 'salary_slabs';
    protected $fillable = [
        'name', 'min_ctc', 'max_ctc',
        'tds_percentage', 'pf_employee_percentage', 'pf_employer_percentage',
        'esi_employee_percentage', 'esi_employer_percentage',
        'applicable_employee_types', 'effective_from', 'effective_to',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'min_ctc' => 'decimal:2',
            'max_ctc' => 'decimal:2',
            'tds_percentage' => 'decimal:2',
            'pf_employee_percentage' => 'decimal:2',
            'pf_employer_percentage' => 'decimal:2',
            'esi_employee_percentage' => 'decimal:2',
            'esi_employer_percentage' => 'decimal:2',
            'applicable_employee_types' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
    public function salaryStructures() { return $this->hasMany(EmployeeSalaryStructure::class, 'salary_slab_id'); }
    public function employees() { return $this->hasMany(Employee::class, 'salary_slab_id'); }

    public function appliesToEmployeeType(?string $primaryType, ?string $labourType = null): bool
    {
        $types = $this->applicable_employee_types;
        if (empty($types)) {
            return true;
        }

        $key = $primaryType === 'staff' ? 'staff' : $labourType;
        return $key !== null && in_array($key, $types, true);
    }

    /** Slab Name is never manually entered — always derived from its salary range. */
    public static function generateName(float $minCtc, float $maxCtc): string
    {
        return '₹' . number_format($minCtc, 0) . ' - ₹' . number_format($maxCtc, 0);
    }

    /**
     * FSD 7.5: "The applicable slab shall be automatically selected during
     * payroll processing based on the employee's salary" — mirrors
     * PfEsiConfig::effectiveOn()'s "latest row effective on this date" style,
     * additionally scoped by salary range and employee type. No longer
     * branch-scoped — Salary Slab is a single company-wide configuration.
     */
    public static function findApplicable(float $salary, ?string $primaryType, ?string $labourType, ?string $date = null): ?self
    {
        $date = $date ?? now()->toDateString();

        return static::where('is_active', true)
            ->where('min_ctc', '<=', $salary)
            ->where('max_ctc', '>=', $salary)
            ->where(fn($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->get()
            ->first(fn($slab) => $slab->appliesToEmployeeType($primaryType, $labourType));
    }
}
