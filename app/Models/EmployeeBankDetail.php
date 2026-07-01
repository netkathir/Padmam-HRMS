<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBankDetail extends Model
{
    protected $table = 'employee_bank_details';
    protected $fillable = ['employee_id', 'bank_name', 'branch_name', 'account_number', 'ifsc_code', 'account_type', 'is_primary'];
    protected function casts(): array { return ['is_primary' => 'boolean']; }
    public function employee() { return $this->belongsTo(Employee::class); }
}
