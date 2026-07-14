<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeExit extends Model
{
    protected $table = 'employee_exits';

    /**
     * Fixed to match the real `employee_exits` migration columns — the
     * previous fillable list (resignation_date/exit_reason/status/
     * approved_by) referenced columns that don't exist anywhere in this
     * table; the real columns are notice_date/exit_date/
     * notice_period_served/reason/full_and_final_status/fnf_amount/
     * fnf_date/processed_by. This model has no callers outside its own
     * class and Employee::exitRecord(), so the fix is isolated.
     */
    protected $fillable = [
        'employee_id', 'exit_type', 'notice_date', 'last_working_date', 'exit_date',
        'notice_period_days', 'notice_period_served', 'reason',
        'full_and_final_status', 'fnf_amount', 'fnf_date', 'remarks', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'notice_date'       => 'date',
            'last_working_date' => 'date',
            'exit_date'         => 'date',
            'fnf_date'          => 'date',
            'fnf_amount'        => 'decimal:2',
        ];
    }

    public function employee()   { return $this->belongsTo(Employee::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
}
