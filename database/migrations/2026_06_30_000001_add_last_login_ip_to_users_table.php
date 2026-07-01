<?php
// File: database/migrations/2026_06_30_000001_add_last_login_ip_to_users_table.php
// Purpose: Add last_login_ip column to users table (missing column caused 419 on login)
// Author: System
// Date: 2026-06-30

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_ip');
        });
    }
};
