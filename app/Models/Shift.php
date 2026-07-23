<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'code', 'start_time', 'end_time',
        'grace_late_entry_minutes', 'grace_early_exit_minutes',
        'applicable_employee_types', 'work_hours', 'is_active',
    ];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'work_hours' => 'decimal:2',
            'applicable_employee_types' => 'array',
        ];
    }

    public function getStartMinutesAttribute(): int
    {
        [$h, $m] = explode(':', $this->start_time);
        return (int)$h * 60 + (int)$m;
    }

    public function getEndMinutesAttribute(): int
    {
        [$h, $m] = explode(':', $this->end_time);
        return (int)$h * 60 + (int)$m;
    }

    public function getWorkMinutesAttribute(): int
    {
        return (int)($this->work_hours * 60);
    }

    /**
     * Total shift duration in minutes — used to validate grace periods don't
     * exceed the shift itself (FSD 7.2).
     */
    public function getDurationMinutesAttribute(): int
    {
        return $this->end_minutes - $this->start_minutes;
    }

    public function appliesToEmployeeType(?string $primaryType, ?string $labourType = null): bool
    {
        $types = $this->applicable_employee_types;
        if (empty($types)) {
            return true;
        }

        $key = $primaryType === 'staff' ? 'staff' : $labourType;
        return $key !== null && in_array($key, $types, true);
    }

    public function employeeShiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }
}
