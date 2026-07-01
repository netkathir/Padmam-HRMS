<?php
/**
 * File: app/Models/ContractWorkerAttendance.php
 * Purpose: Daily attendance records for contract workers, separate from regular employee attendance.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractWorkerAttendance extends Model
{
    protected $table = 'contract_worker_attendance';

    protected $fillable = [
        'contract_worker_id', 'contractor_id', 'date',
        'status', 'in_time', 'out_time', 'ot_hours',
        'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'     => 'date',
            'ot_hours' => 'decimal:2',
        ];
    }

    public function worker()     { return $this->belongsTo(ContractWorker::class, 'contract_worker_id'); }
    public function contractor() { return $this->belongsTo(Contractor::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
}
