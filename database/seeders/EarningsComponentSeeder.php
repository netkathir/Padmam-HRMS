<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EarningsComponentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('earnings_components')->insert([
            ['name' => 'Basic Salary',      'code' => 'BASIC', 'type' => 'percentage', 'calculation_base' => 'ctc',          'percentage' => 40, 'is_taxable' => 1, 'is_pf_applicable' => 1, 'is_esi_applicable' => 1, 'sort_order' => 1],
            ['name' => 'House Rent Allow.', 'code' => 'HRA',   'type' => 'percentage', 'calculation_base' => 'basic_salary', 'percentage' => 40, 'is_taxable' => 1, 'is_pf_applicable' => 0, 'is_esi_applicable' => 0, 'sort_order' => 2],
            ['name' => 'Dearness Allow.',   'code' => 'DA',    'type' => 'percentage', 'calculation_base' => 'basic_salary', 'percentage' => 10, 'is_taxable' => 1, 'is_pf_applicable' => 1, 'is_esi_applicable' => 1, 'sort_order' => 3],
            ['name' => 'Transport Allow.',  'code' => 'TA',    'type' => 'fixed',      'calculation_base' => null,           'percentage' => null, 'is_taxable' => 0, 'is_pf_applicable' => 0, 'is_esi_applicable' => 0, 'sort_order' => 4],
            ['name' => 'Medical Allow.',    'code' => 'MA',    'type' => 'fixed',      'calculation_base' => null,           'percentage' => null, 'is_taxable' => 0, 'is_pf_applicable' => 0, 'is_esi_applicable' => 0, 'sort_order' => 5],
            ['name' => 'Special Allow.',    'code' => 'SA',    'type' => 'formula',    'calculation_base' => 'ctc_balance',  'percentage' => null, 'is_taxable' => 1, 'is_pf_applicable' => 0, 'is_esi_applicable' => 1, 'sort_order' => 6],
            ['name' => 'Overtime',          'code' => 'OT',    'type' => 'fixed',      'calculation_base' => null,           'percentage' => null, 'is_taxable' => 1, 'is_pf_applicable' => 0, 'is_esi_applicable' => 0, 'sort_order' => 7],
        ]);
    }
}
