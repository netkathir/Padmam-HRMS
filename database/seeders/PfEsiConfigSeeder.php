<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PfEsiConfigSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pf_esi_config')->insert([
            'effective_from'   => '2024-04-01',
            'pf_employee_pct'  => 12.00,
            'pf_employer_pct'  => 12.00,
            'pf_wage_ceiling'  => 15000.00,
            'esi_employee_pct' => 0.75,
            'esi_employer_pct' => 3.25,
            'esi_wage_ceiling' => 21000.00,
            'is_active'        => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
