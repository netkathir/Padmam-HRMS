<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_code', 'branch_id', 'department_id', 'designation_id',
        'employee_type_id', 'primary_employee_type', 'labour_type', 'contractor_id', 'shift_id', 'reporting_to', 'salary_slab_id',
        'first_name', 'last_name', 'date_of_birth', 'gender', 'marital_status',
        'blood_group', 'nationality', 'religion',
        'personal_email', 'official_email', 'phone', 'alternate_phone',
        'emergency_contact_name', 'emergency_contact_phone',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'date_of_joining', 'date_of_confirmation', 'probation_end_date', 'status',
        'aadhaar_number', 'pan_number', 'uan_number', 'esi_number',
        'passport_number', 'passport_expiry',
        'profile_photo', 'is_pf_applicable', 'is_esi_applicable', 'is_tds_applicable',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'          => 'date',
            'date_of_joining'        => 'date',
            'date_of_confirmation'   => 'date',
            'probation_end_date'     => 'date',
            'passport_expiry'        => 'date',
            'is_pf_applicable'       => 'boolean',
            'is_esi_applicable'      => 'boolean',
            'is_tds_applicable'      => 'boolean',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeOfDepartment($query, int $deptId) { return $query->where('department_id', $deptId); }
    public function scopeOfBranch($query, int $branchId) { return $query->where('branch_id', $branchId); }
    public function scopeStaff($query) { return $query->where('primary_employee_type', 'staff'); }
    public function scopeCompanyLabour($query) { return $query->where('primary_employee_type', 'labour')->where('labour_type', 'company_labour'); }

    // ── Computed ───────────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo
            ? asset('storage/' . $this->profile_photo)
            : asset('images/default-avatar.png');
    }

    // ── Relationships ──────────────────────────────────────────────
    public function branch()       { return $this->belongsTo(Branch::class); }
    public function department()   { return $this->belongsTo(Department::class); }
    public function designation()  { return $this->belongsTo(Designation::class); }
    public function employeeType() { return $this->belongsTo(EmployeeType::class); }
    public function contractor()   { return $this->belongsTo(Contractor::class); }
    public function shift()        { return $this->belongsTo(Shift::class); }
    public function salarySlab()   { return $this->belongsTo(SalarySlab::class); }
    public function user()         { return $this->hasOne(User::class); }

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
