<?php
// File: database/migrations/2026_07_13_000001_add_remember_token_to_users_table.php
// Purpose: The users table was created without the remember_token column that
//          Laravel's Authenticatable contract requires for "Keep Me Logged In"
//          (Auth::attempt($credentials, true)) and password-reset token
//          regeneration — both currently fail with a 42S22 "column not found"
//          error. Adds it nullable so existing rows are unaffected.
// Author: System
// Date: 2026-07-13

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
