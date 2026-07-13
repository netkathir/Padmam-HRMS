<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'code', 'start_time', 'end_time', 'break_minutes',
        'grace_late_entry_minutes', 'grace_early_exit_minutes',
        'applicable_employee_types', 'work_hours', 'is_overnight', 'is_active',
    ];
    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
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
     * Total shift duration in minutes, overnight-aware (end time on the next
     * day when is_overnight is set) — used to validate grace periods don't
     * exceed the shift itself (FSD 7.2).
     */
    public function getDurationMinutesAttribute(): int
    {
        $end = $this->end_minutes;
        if ($this->is_overnight && $end <= $this->start_minutes) {
            $end += 24 * 60;
        }
        return $end - $this->start_minutes;
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

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'shift_branches');
    }

    public function employeeShiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }
}
