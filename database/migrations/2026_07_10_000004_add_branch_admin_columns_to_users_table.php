<?php
// File: database/migrations/2026_07_10_000004_add_branch_admin_columns_to_users_table.php
// Purpose: Additive columns for the new Branch Administration Users screen. All
//          nullable/defaulted — the existing Users screen (App\Http\Controllers\
//          UserController, users.* routes/views) never references them, so its
//          behavior is unaffected. Accounts created via Branch Administration are
//          real rows in this same table so they can actually log in.
// Author: System
// Date: 2026-07-10

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)->nullable()->after('role_id');
            $table->unsignedSmallInteger('branch_id')->nullable()->after('user_type');
            $table->string('mobile', 20)->nullable()->after('branch_id');
            $table->boolean('force_password_change')->default(false)->after('is_active');
            $table->date('account_expiry_date')->nullable()->after('force_password_change');
            $table->boolean('is_locked')->default(false)->after('account_expiry_date');
            $table->unsignedInteger('created_by')->nullable()->after('is_locked');
            $table->unsignedInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'user_type', 'branch_id', 'mobile', 'force_password_change',
                'account_expiry_date', 'is_locked', 'created_by', 'updated_by',
            ]);
        });
    }
};
