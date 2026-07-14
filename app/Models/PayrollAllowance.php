<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAllowance extends Model
{
    protected $table = 'payroll_allowances';
    protected $fillable = ['payroll_id', 'earnings_component_id', 'name', 'amount', 'remarks'];
    protected function casts(): array { return ['amount' => 'decimal:2']; }
    public function payroll()   { return $this->belongsTo(PayrollRecord::class, 'payroll_id'); }
    public function component() { return $this->belongsTo(EarningsComponent::class, 'earnings_component_id'); }
}
