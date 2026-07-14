<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use SoftDeletes;

    protected $table = 'leave_types';
    protected $fillable = [
        'name', 'code', 'is_paid', 'applicable_employee_types', 'is_active',
    ];
    protected function casts(): array {
        return [
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'applicable_employee_types' => 'array',
        ];
    }
    public function balances() { return $this->hasMany(LeaveBalance::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }

    public function appliesToEmployeeType(?string $primaryType, ?string $labourType = null): bool
    {
        $types = $this->applicable_employee_types;
        if (empty($types)) {
            return true;
        }

        $key = $primaryType === 'staff' ? 'staff' : $labourType;
        return $key !== null && in_array($key, $types, true);
    }
}
