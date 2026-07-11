<?php
// File: database/migrations/2026_07_11_000001_add_remarks_to_audit_logs_and_users_tables.php
// Purpose: Gap-fix — the original spec requires a "Remarks / Reason" field on
//          the Audit Log and a "Remarks" field on Users; both were previously
//          collected in the form (Users) or supportable (Audit Log) but never
//          actually persisted. Additive columns only.
// Author: System
// Date: 2026-07-11

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('new_values');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('remarks', 1000)->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
