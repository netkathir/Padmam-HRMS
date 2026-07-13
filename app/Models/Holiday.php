<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'date', 'type', 'branch_id', 'calendar_name', 'description',
        'is_paid', 'applicable_employee_types', 'is_active', 'year',
    ];
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'applicable_employee_types' => 'array',
        ];
    }
    public function branch() { return $this->belongsTo(Branch::class); }

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
