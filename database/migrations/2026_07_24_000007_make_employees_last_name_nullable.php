<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * last_name has always been validated as optional (Employee Registration
 * FSD — Personal Information tab) but the column itself was still NOT NULL,
 * so leaving it blank (submitted as an empty string, normalized to null by
 * ConvertEmptyStringsToNull) threw a raw SQL error at insert time instead of
 * a validation error — for both draft and complete registrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employees MODIFY last_name VARCHAR(100) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE employees SET last_name = '' WHERE last_name IS NULL");
        DB::statement('ALTER TABLE employees MODIFY last_name VARCHAR(100) NOT NULL');
    }
};
