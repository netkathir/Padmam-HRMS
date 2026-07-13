<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtRate extends Model
{
    protected $table = 'ot_rates';
    protected $fillable = [
        'name', 'employee_type_id', 'department_id', 'rate_type',
        'multiplier', 'fixed_amount', 'max_ot_hours_day', 'is_active',
    ];
    protected function casts(): array {
        return [
            'multiplier' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'max_ot_hours_day' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
    public function employeeType() { return $this->belongsTo(EmployeeType::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
