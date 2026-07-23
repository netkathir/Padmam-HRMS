<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySlab extends Model
{
    use SoftDeletes;

    protected $table = 'salary_slabs';
    protected $fillable = [
        'name', 'salary_from', 'salary_to',
        'tds_percentage', 'pf_employee_percentage', 'pf_employer_percentage',
        'esi_employee_percentage', 'esi_employer_percentage',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'salary_from' => 'decimal:2',
            'salary_to' => 'decimal:2',
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
    public function components() { return $this->hasMany(SalarySlabComponent::class); }
    public function earningsComponents() { return $this->components()->where('component_type', 'earning'); }

    // ── Computed salary breakdown — Basic Salary is entered per-employee
    // (Employee Slab's Designation & Salary section), never stored on the
    // slab itself; every figure here is derived from that employee-entered
    // Basic Salary plus this slab's own PF/ESI/TDS percentages. PF is
    // calculated on Basic Salary, ESI/TDS on Gross — standard convention. ──

    public function grossSalary(float $basicSalary): float
    {
        return round($basicSalary, 2);
    }

    public function pfEmployee(float $basicSalary): float
    {
        return round($basicSalary * (float) $this->pf_employee_percentage / 100, 2);
    }

    public function pfEmployer(float $basicSalary): float
    {
        return round($basicSalary * (float) $this->pf_employer_percentage / 100, 2);
    }

    public function esiEmployee(float $basicSalary): float
    {
        return round($this->grossSalary($basicSalary) * (float) $this->esi_employee_percentage / 100, 2);
    }

    public function esiEmployer(float $basicSalary): float
    {
        return round($this->grossSalary($basicSalary) * (float) $this->esi_employer_percentage / 100, 2);
    }

    public function tds(float $basicSalary): float
    {
        return round($this->grossSalary($basicSalary) * (float) $this->tds_percentage / 100, 2);
    }

    public function employerContributions(float $basicSalary): float
    {
        return round($this->pfEmployer($basicSalary) + $this->esiEmployer($basicSalary), 2);
    }

    public function totalDeductions(float $basicSalary): float
    {
        return round($this->pfEmployee($basicSalary) + $this->esiEmployee($basicSalary) + $this->tds($basicSalary), 2);
    }

    public function netSalary(float $basicSalary): float
    {
        return round($this->grossSalary($basicSalary) - $this->totalDeductions($basicSalary), 2);
    }

    public function ctc(float $basicSalary): float
    {
        return round($this->grossSalary($basicSalary) + $this->employerContributions($basicSalary), 2);
    }
}
