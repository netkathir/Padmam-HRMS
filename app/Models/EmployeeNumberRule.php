<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNumberRule extends Model
{
    protected $fillable = [
        'rule_id', 'employee_category', 'branch_id', 'contractor_id', 'prefix',
        'include_branch_code', 'include_contractor_code', 'separator',
        'sequence_start', 'sequence_length', 'include_financial_year',
        'include_calendar_year', 'reset_frequency', 'allow_manual_override',
    ];

    protected function casts(): array
    {
        return [
            'include_branch_code' => 'boolean',
            'include_contractor_code' => 'boolean',
            'include_financial_year' => 'boolean',
            'include_calendar_year' => 'boolean',
            'allow_manual_override' => 'boolean',
        ];
    }

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function contractor() { return $this->belongsTo(Contractor::class); }
}
