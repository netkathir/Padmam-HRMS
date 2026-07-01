<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPayment extends Model
{
    protected $table = 'payroll_payments';
    protected $fillable = ['payroll_id', 'payment_date', 'payment_mode', 'reference_number', 'amount', 'remarks', 'created_by'];
    protected function casts(): array { return ['payment_date' => 'date', 'amount' => 'decimal:2']; }
    public function payroll() { return $this->belongsTo(PayrollRecord::class, 'payroll_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
