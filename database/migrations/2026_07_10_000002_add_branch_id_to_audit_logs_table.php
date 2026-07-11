<?php
// File: database/migrations/2026_07_10_000002_add_branch_id_to_audit_logs_table.php
// Purpose: Let audit rows record which branch an action was performed in. No FK —
//          audit rows must remain even if the referenced branch is later deleted.
// Author: System
// Date: 2026-07-10

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('user_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
