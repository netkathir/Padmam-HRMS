<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBankDetail extends Model
{
    protected $table = 'employee_bank_details';
    protected $fillable = [
        'employee_id', 'payment_mode', 'account_holder_name', 'bank_name', 'bank_id', 'branch_name',
        'account_number', 'ifsc_code', 'account_type', 'is_primary', 'is_verified', 'verified_by', 'verified_at',
    ];
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function bank()     { return $this->belongsTo(Bank::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }

    /** FSD 10.3.5 — "Sensitive bank data shall be masked for users without permission." */
    public function getMaskedAccountNumberAttribute(): string
    {
        $number = (string) $this->account_number;
        return $number === '' ? '' : str_repeat('•', max(0, strlen($number) - 4)) . substr($number, -4);
    }
}
