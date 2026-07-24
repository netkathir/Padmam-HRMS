<?php
// File: database/migrations/2026_07_23_000003_add_unique_index_to_users_name.php
// Purpose: Module 11 (FSD 15.1) — "User Name: Text, mandatory, unique." Adds a
//          unique index on users.name. Guarded: if any existing duplicate
//          names are already present in production data, the index is
//          skipped (not silently dropping/renaming anyone's data) and the
//          gap is left to be resolved manually — the app-level `unique:
//          users,name` validation rule (added alongside this migration)
//          still prevents any NEW duplicate from being created either way.
// Author: System
// Date: 2026-07-23

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexExists = collect(Schema::getIndexes('users'))->pluck('name')->contains('users_name_unique');
        if ($indexExists) {
            return;
        }

        $hasDuplicates = DB::table('users')
            ->select('name')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        $indexExists = collect(Schema::getIndexes('users'))->pluck('name')->contains('users_name_unique');
        if ($indexExists) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        }
    }
};
