<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'start_date', 'end_date',
        'is_paid', 'applicable_employee_types', 'is_active',
    ];
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'applicable_employee_types' => 'array',
        ];
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

    /** Whether $date falls within this holiday's [start_date, end_date] range (inclusive). */
    public function containsDate(\Carbon\Carbon $date): bool
    {
        return $date->toDateString() >= $this->start_date->toDateString()
            && $date->toDateString() <= $this->end_date->toDateString();
    }
}
