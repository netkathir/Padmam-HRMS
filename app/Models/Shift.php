<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'branch_id', 'name', 'code', 'start_time', 'end_time',
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

    /**
     * MySQL's TIME column type round-trips as "H:i:s" (e.g. "21:00:00"),
     * but every form field and validation rule in this app works in plain
     * "H:i" — normalizing here (rather than in every Blade view) means the
     * raw value is always safe to drop straight into an <input type="time">
     * or re-validate with date_format:H:i.
     */
    public function getStartTimeAttribute($value): ?string
    {
        return $value ? substr($value, 0, 5) : $value;
    }

    public function getEndTimeAttribute($value): ?string
    {
        return $value ? substr($value, 0, 5) : $value;
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
     * exceed the shift itself (FSD 7.2). No explicit overnight flag: an End
     * Time strictly earlier than Start Time is inferred to cross midnight
     * (e.g. 22:00 -> 06:00 is an 8-hour shift), purely from the 24-hour
     * values themselves. An End Time EQUAL to Start Time is NOT wrapped —
     * that's a same-time data-entry mistake, not a 24-hour shift, and must
     * still produce a zero/invalid duration.
     */
    public function getDurationMinutesAttribute(): int
    {
        $end = $this->end_minutes;
        if ($end < $this->start_minutes) {
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

    public function employeeShiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
