<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_code', 'biometric_number', 'branch_id', 'department_id', 'designation_id',
        'employee_type_id', 'primary_employee_type', 'labour_type', 'contractor_id',
        'contractor_employee_number', 'work_order_number', 'labour_category', 'contractor_rate', 'contractor_remarks',
        'designation_employee_category', 'designation_employee_type', 'designation_contractor_id',
        'shift_id', 'weekly_off_rule_id', 'attendance_rule_id', 'payroll_rule_id',
        'reporting_to', 'salary_slab_id',
        'first_name', 'middle_name', 'last_name', 'display_name', 'date_of_birth', 'gender', 'marital_status',
        'blood_group', 'nationality', 'religion', 'father_spouse_name',
        'personal_email', 'official_email', 'phone', 'alternate_phone',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
        'address_line1', 'address_line2', 'city', 'district', 'state', 'pincode',
        'permanent_address_line1', 'permanent_address_line2', 'permanent_city', 'permanent_district', 'permanent_state', 'permanent_pincode',
        'date_of_joining', 'date_of_confirmation', 'probation_end_date', 'contract_start_date', 'contract_end_date', 'status',
        'aadhaar_number', 'pan_number', 'uan_number', 'pf_number', 'esi_number', 'tds_number',
        'passport_number', 'passport_expiry',
        'profile_photo', 'is_pf_applicable', 'is_esi_applicable', 'is_tds_applicable', 'is_earnings_applicable', 'is_ot_applicable', 'ot_hourly_rate',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'          => 'date',
            'date_of_joining'        => 'date',
            'date_of_confirmation'   => 'date',
            'probation_end_date'     => 'date',
            'contract_start_date'    => 'date',
            'contract_end_date'      => 'date',
            'passport_expiry'        => 'date',
            'is_pf_applicable'       => 'boolean',
            'is_esi_applicable'      => 'boolean',
            'is_tds_applicable'      => 'boolean',
            'is_earnings_applicable' => 'boolean',
            'is_ot_applicable'       => 'boolean',
            'contractor_rate'        => 'decimal:2',
            'ot_hourly_rate'         => 'decimal:2',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeOfDepartment($query, int $deptId) { return $query->where('department_id', $deptId); }
    public function scopeOfBranch($query, int $branchId) { return $query->where('branch_id', $branchId); }
    public function scopeStaff($query) { return $query->where('primary_employee_type', 'staff'); }
    public function scopeCompanyLabour($query) { return $query->where('primary_employee_type', 'labour')->where('labour_type', 'company_labour'); }
    public function scopeContractLabour($query) { return $query->where('primary_employee_type', 'labour')->where('labour_type', 'contract_labour'); }

    // ── Computed ───────────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    /** FSD 10.3.1 — "Display Name ... Defaults from employee name." */
    public function getDisplayNameOrDefaultAttribute(): string
    {
        return $this->display_name ?: $this->full_name;
    }

    /** FSD 10.3.1 — "Age ... Calculated from date of birth." */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * FSD Tab 1 — "System Classification must be auto-determined and
     * read-only." Derived purely from the two stored classification
     * columns (primary_employee_type/labour_type) — there is no separate
     * classification column of its own, this is just its display form.
     */
    public function getSystemClassificationAttribute(): string
    {
        $key = $this->primary_employee_type === 'staff' ? 'staff' : $this->labour_type;

        return config("employee_types.$key", config('employee_types.staff'));
    }

    /** Employee Master — "Designation & Salary" section's Type field, display form. */
    public function getDesignationEmployeeTypeLabelAttribute(): ?string
    {
        return match ($this->designation_employee_type) {
            'staff' => 'Staff',
            'labor' => 'Labor',
            'contractor_staff' => 'Contractor Staff',
            'contractor_labor' => 'Contractor Labor',
            default => null,
        };
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo
            ? asset('storage/' . $this->profile_photo)
            : asset('images/default-avatar.png');
    }

    /** FSD 10.8 — "system shall warn before contract expiry." */
    public function isContractExpiringSoon(): bool
    {
        return $this->contract_end_date
            && $this->contract_end_date->between(now(), now()->addDays(30));
    }

    public function isContractExpired(): bool
    {
        return $this->contract_end_date && $this->contract_end_date->isPast();
    }

    /**
     * FSD 14.2 — "Sensitive fields shall be masked based on user permission."
     * Thin wrappers over App\Support\Reports\ReportMasking so the masking
     * rule itself lives in exactly one place.
     */
    public function getMaskedPanNumberAttribute(): string
    {
        return \App\Support\Reports\ReportMasking::mask($this->pan_number);
    }

    public function getMaskedUanNumberAttribute(): string
    {
        return \App\Support\Reports\ReportMasking::mask($this->uan_number);
    }

    public function getMaskedPfNumberAttribute(): string
    {
        return \App\Support\Reports\ReportMasking::mask($this->pf_number);
    }

    public function getMaskedEsiNumberAttribute(): string
    {
        return \App\Support\Reports\ReportMasking::mask($this->esi_number);
    }

    public function getMaskedAadhaarNumberAttribute(): string
    {
        return \App\Support\Reports\ReportMasking::mask($this->aadhaar_number);
    }

    // ── Relationships ──────────────────────────────────────────────
    public function branch()       { return $this->belongsTo(Branch::class); }
    public function department()   { return $this->belongsTo(Department::class); }
    public function designation()  { return $this->belongsTo(Designation::class); }
    public function employeeType() { return $this->belongsTo(EmployeeType::class); }
    public function contractor()   { return $this->belongsTo(Contractor::class); }

    /** Employee Master — "Designation & Salary" section's own Contractor Name field (distinct from Tab 6's contractor_id). */
    public function designationContractor() { return $this->belongsTo(Contractor::class, 'designation_contractor_id'); }
    public function shift()        { return $this->belongsTo(Shift::class); }
    public function salarySlab()   { return $this->belongsTo(SalarySlab::class); }
    public function user()         { return $this->hasOne(User::class); }

    /** FSD 10.3.3 — per-employee Rule Engine overrides (nullable; null = automatic resolution, unchanged). */
    public function weeklyOffRuleOverride()  { return $this->belongsTo(BusinessRule::class, 'weekly_off_rule_id'); }
    public function attendanceRuleOverride() { return $this->belongsTo(BusinessRule::class, 'attendance_rule_id'); }
    public function payrollRuleOverride()    { return $this->belongsTo(BusinessRule::class, 'payroll_rule_id'); }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'reporting_to');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class)->whereNull('deleted_at');
    }

    public function bankDetails()
    {
        return $this->hasMany(EmployeeBankDetail::class)->orderByDesc('is_primary');
    }

    public function currentSalary()
    {
        return $this->hasOne(EmployeeSalaryStructure::class)->where('is_current', true);
    }

    public function salaryHistory()
    {
        return $this->hasMany(EmployeeSalaryStructure::class)->orderByDesc('effective_from');
    }

    public function currentShiftAssignment()
    {
        return $this->hasOne(EmployeeShiftAssignment::class)->where('is_current', true);
    }

    public function exitRecord()
    {
        return $this->hasOne(EmployeeExit::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }
}
