<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    protected $table = 'payroll_deductions';
    protected $fillable = ['payroll_id', 'deductions_component_id', 'name', 'amount', 'remarks'];
    protected function casts(): array { return ['amount' => 'decimal:2']; }
    public function payroll()   { return $this->belongsTo(PayrollRecord::class, 'payroll_id'); }
}
