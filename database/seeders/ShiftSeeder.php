<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shifts')->insert([
            ['name' => 'General Shift', 'code' => 'GEN', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'break_minutes' => 60, 'grace_minutes' => 10, 'work_hours' => 8.00, 'is_overnight' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Morning Shift', 'code' => 'MOR', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'break_minutes' => 30, 'grace_minutes' => 10, 'work_hours' => 8.00, 'is_overnight' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Evening Shift', 'code' => 'EVE', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'break_minutes' => 30, 'grace_minutes' => 10, 'work_hours' => 8.00, 'is_overnight' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Night Shift',   'code' => 'NGT', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'break_minutes' => 30, 'grace_minutes' => 10, 'work_hours' => 8.00, 'is_overnight' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
