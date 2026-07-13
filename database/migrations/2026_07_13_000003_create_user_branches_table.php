<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard FSD — "Branch multi-select (authorized users only)". Deliberately
 * named `user_branches`, not the Laravel-conventional alphabetical
 * `branch_user`, to avoid colliding with the existing `branch_user` ROLE name
 * (roles.name = 'branch_user' means "a regular staff user of one branch";
 * this table is an unrelated many-to-many authorization grant).
 *
 * This is additive, opt-in authorization on top of the existing single-branch
 * model — it does not change Super Admin (unrestricted, unchanged),
 * Branch Head/Branch User (single branch via users.branch_id, unchanged).
 * It only gives OTHER roles (hr_admin, payroll_admin, management, ...) a way
 * to be granted a specific set of branches, consumed by
 * BranchScope::authorizedBranchIds().
 *
 * Column widths deliberately match users.id (int unsigned) and branches.id
 * (smallint unsigned) exactly, per this codebase's existing convention (see
 * 2026_07_10_000004_add_branch_admin_columns_to_users_table.php) rather than
 * Laravel's generic bigint foreignId() default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedSmallInteger('branch_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
    }
};
