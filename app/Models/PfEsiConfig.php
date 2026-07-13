<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfEsiConfig extends Model
{
    protected $table = 'pf_esi_config';
    protected $fillable = [
        'effective_from', 'pf_employee_pct', 'pf_employer_pct', 'pf_wage_ceiling',
        'esi_employee_pct', 'esi_employer_pct', 'esi_wage_ceiling', 'is_active',
    ];
    protected function casts(): array {
        return [
            'effective_from'   => 'date',
            'pf_employee_pct'  => 'decimal:2',
            'pf_employer_pct'  => 'decimal:2',
            'pf_wage_ceiling'  => 'decimal:2',
            'esi_employee_pct' => 'decimal:2',
            'esi_employer_pct' => 'decimal:2',
            'esi_wage_ceiling' => 'decimal:2',
            'is_active'        => 'boolean',
        ];
    }

    /**
     * The config actually effective for a given date — the one with the
     * latest effective_from that is still <= the date, not simply the most
     * recently created row. Used by PayrollController::generate() so a
     * retroactive/back-dated payroll run applies the rates that were
     * actually in force during that period.
     */
    public static function effectiveOn(string $date): ?self
    {
        return static::where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->first();
    }
}
