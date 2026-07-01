<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('company_profile')->insert([
            'name'                 => 'My Company Pvt. Ltd.',
            'short_name'           => 'MyCompany',
            'financial_year_start' => 4,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }
}
