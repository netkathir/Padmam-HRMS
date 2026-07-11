<?php
// File: database/migrations/2026_07_10_000001_add_branch_admin_columns_to_branches_table.php
// Purpose: Additive columns for the new Branch Administration module's richer branch
//          field set. All nullable — the existing Masters > Branches screen never
//          references these columns, so its behavior is unaffected.
// Author: System
// Date: 2026-07-10

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('district', 100)->nullable()->after('state');
            $table->string('contact_person', 150)->nullable()->after('email');
            $table->unsignedInteger('branch_head_user_id')->nullable()->after('manager_id');
            $table->date('start_date')->nullable()->after('is_active');
            $table->unsignedInteger('created_by')->nullable()->after('start_date');
            $table->unsignedInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('branch_head_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['branch_head_user_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['district', 'contact_person', 'branch_head_user_id', 'start_date', 'created_by', 'updated_by']);
        });
    }
};
