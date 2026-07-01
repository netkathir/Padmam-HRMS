<?php
/**
 * File: app/Models/ContractWorker.php
 * Purpose: Contract worker model — external/contract labourer under a contractor with wage configuration.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractWorker extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contractor_id', 'name', 'gender', 'phone',
        'id_proof_type', 'id_proof_number',
        'skill_type', 'wage_type', 'wage_amount',
        'joining_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'wage_amount'  => 'decimal:2',
        ];
    }

    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function contractor()     { return $this->belongsTo(Contractor::class); }
    public function attendance()     { return $this->hasMany(ContractWorkerAttendance::class); }
    public function payrollRecords() { return $this->hasMany(ContractWorkerPayroll::class); }
}
