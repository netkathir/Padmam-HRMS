<?php
// File: database/migrations/2026_07_23_000004_make_users_email_nullable.php
// Purpose: Module 11 (FSD 15.1) — "Email Address: optional." The
//          `validateUser()` rule was relaxed to nullable, but the DB column
//          itself was still NOT NULL — submitting a user with no email would
//          pass validation and then fail with a raw SQL error instead of a
//          friendly message. This closes that gap. Uses raw SQL (not
//          Blueprint::change(), which needs doctrine/dbal — not installed in
//          this project) matching the established pattern elsewhere in this
//          codebase for column-definition changes.
// Author: System
// Date: 2026-07-23

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $column = collect(DB::select("SHOW COLUMNS FROM `users` LIKE 'email'"))->first();
        if ($column && strtoupper($column->Null) === 'NO') {
            DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(150) NULL');
        }
    }

    public function down(): void
    {
        // Not reversed automatically — reversing would require first
        // resolving any NULL emails that may have been created in the
        // meantime, which this migration cannot safely assume how to do.
    }
};
