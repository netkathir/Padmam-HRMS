<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySlab extends Model
{
    use SoftDeletes;

    protected $table = 'salary_slabs';
    protected $fillable = [
        'name', 'min_ctc', 'max_ctc', 'basic_salary',
        'tds_percentage', 'pf_employee_percentage', 'pf_employer_percentage',
        'esi_employee_percentage', 'esi_employer_percentage',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'min_ctc' => 'decimal:2',
            'max_ctc' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'tds_percentage' => 'decimal:2',
            'pf_employee_percentage' => 'decimal:2',
            'pf_employer_percentage' => 'decimal:2',
            'esi_employee_percentage' => 'decimal:2',
            'esi_employer_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
    public function salaryStructures() { return $this->hasMany(EmployeeSalaryStructure::class, 'salary_slab_id'); }
    public function employees() { return $this->hasMany(Employee::class, 'salary_slab_id'); }

    /** Employee Master — "Designation & Salary" is now entirely inherited from this slab's own components. */
    public function components() { return $this->hasMany(SalarySlabComponent::class, 'salary_slab_id'); }

    /** Slab Name is never manually entered — always derived from its salary range. */
    public static function generateName(float $minCtc, float $maxCtc): string
    {
        return '₹' . number_format($minCtc, 0) . ' - ₹' . number_format($maxCtc, 0);
    }

    /**
     * FSD 7.5: "The applicable slab shall be automatically selected during
     * payroll processing based on the employee's salary" — matched purely by
     * CTC range now that Applicability (employee type) and Effective Period
     * were removed from the Salary Slab; $primaryType/$labourType/$date are
     * kept as accepted (ignored) parameters so existing call sites (e.g.
     * PayrollController) don't need to change.
     */
    public static function findApplicable(float $salary, ?string $primaryType = null, ?string $labourType = null, ?string $date = null): ?self
    {
        return static::where('is_active', true)
            ->where('min_ctc', '<=', $salary)
            ->where('max_ctc', '>=', $salary)
            ->first();
    }

    // ── Computed salary breakdown — the single source of truth an employee
    // assigned to this slab inherits verbatim (FSD: "no duplicate or manual
    // salary calculations should exist in the Employee module"). PF is
    // calculated on Basic Salary, ESI/TDS on Gross — standard convention. ──

    public function getEarningComponentsAttribute()
    {
        return $this->components->where('component_type', 'earning')->values();
    }

    public function getDeductionComponentsAttribute()
    {
        return $this->components->where('component_type', 'deduction')->values();
    }

    public function getGrossSalaryAttribute(): float
    {
        return round((float) $this->basic_salary + $this->earning_components->sum('calculated_amount'), 2);
    }

    public function getPfEmployeeAttribute(): float
    {
        return round((float) $this->basic_salary * (float) $this->pf_employee_percentage / 100, 2);
    }

    public function getPfEmployerAttribute(): float
    {
        return round((float) $this->basic_salary * (float) $this->pf_employer_percentage / 100, 2);
    }

    public function getEsiEmployeeAttribute(): float
    {
        return round($this->gross_salary * (float) $this->esi_employee_percentage / 100, 2);
    }

    public function getEsiEmployerAttribute(): float
    {
        return round($this->gross_salary * (float) $this->esi_employer_percentage / 100, 2);
    }

    public function getTdsAttribute(): float
    {
        return round($this->gross_salary * (float) $this->tds_percentage / 100, 2);
    }

    public function getEmployerContributionsAttribute(): float
    {
        return round($this->pf_employer + $this->esi_employer, 2);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return round($this->deduction_components->sum('calculated_amount') + $this->pf_employee + $this->esi_employee + $this->tds, 2);
    }

    public function getNetSalaryAttribute(): float
    {
        return round($this->gross_salary - $this->total_deductions, 2);
    }

    public function getCtcAttribute(): float
    {
        return round($this->gross_salary + $this->employer_contributions, 2);
    }
}
