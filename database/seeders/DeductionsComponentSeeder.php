<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeductionsComponentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('deductions_components')->insert([
            ['name' => 'PF Employee',       'code' => 'PF_EMP',  'type' => 'statutory', 'calculation_base' => 'pf_wage',     'percentage' => 12,   'is_statutory' => 1, 'sort_order' => 1],
            ['name' => 'ESI Employee',      'code' => 'ESI_EMP', 'type' => 'statutory', 'calculation_base' => 'gross_salary', 'percentage' => 0.75, 'is_statutory' => 1, 'sort_order' => 2],
            ['name' => 'Professional Tax',  'code' => 'PT',      'type' => 'fixed',     'calculation_base' => null,           'percentage' => null, 'is_statutory' => 1, 'sort_order' => 3],
            ['name' => 'TDS',               'code' => 'TDS',     'type' => 'fixed',     'calculation_base' => null,           'percentage' => null, 'is_statutory' => 1, 'sort_order' => 4],
            ['name' => 'Advance Recovery',  'code' => 'ADV',     'type' => 'fixed',     'calculation_base' => null,           'percentage' => null, 'is_statutory' => 0, 'sort_order' => 5],
            ['name' => 'Loss of Pay',       'code' => 'LOP_DED', 'type' => 'fixed',     'calculation_base' => 'daily_wage',   'percentage' => null, 'is_statutory' => 0, 'sort_order' => 6],
        ]);
    }
}
