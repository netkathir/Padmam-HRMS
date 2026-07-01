<?php
/**
 * File: app/Models/ContractWorkerPayroll.php
 * Purpose: Wage/payroll records for contract workers calculated from attendance.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractWorkerPayroll extends Model
{
    protected $table = 'contract_worker_payroll';

    protected $fillable = [
        'contractor_id', 'contract_worker_id', 'month', 'year',
        'total_days', 'present_days', 'absent_days', 'half_days', 'ot_hours',
        'wage_type', 'wage_amount', 'total_wages', 'ot_amount',
        'gross_wages', 'deductions', 'net_wages',
        'payment_status', 'payment_date', 'payment_remarks', 'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'present_days' => 'decimal:2',
            'absent_days'  => 'decimal:2',
            'half_days'    => 'decimal:2',
            'ot_hours'     => 'decimal:2',
            'wage_amount'  => 'decimal:2',
            'total_wages'  => 'decimal:2',
            'ot_amount'    => 'decimal:2',
            'gross_wages'  => 'decimal:2',
            'deductions'   => 'decimal:2',
            'net_wages'    => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function worker()     { return $this->belongsTo(ContractWorker::class, 'contract_worker_id'); }
    public function contractor() { return $this->belongsTo(Contractor::class); }
    public function generator()  { return $this->belongsTo(User::class, 'generated_by'); }
}
