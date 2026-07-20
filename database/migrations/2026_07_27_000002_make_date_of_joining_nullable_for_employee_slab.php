<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * People Module Update — Date of Joining moved to the Employee Slab's
 * Employment Information section; Create Employee's own Steps 1-3 no longer
 * collect it, so it must be nullable between creation and Employee Slab
 * being completed (see 2026_07_27_000001, which covers the other
 * Employment Information columns — this one was missed there).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employees MODIFY date_of_joining DATE NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employees MODIFY date_of_joining DATE NOT NULL');
    }
};
