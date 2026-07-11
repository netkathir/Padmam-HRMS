<?php
// File: database/migrations/2026_07_12_000001_add_branch_id_to_contractors_table.php
// Purpose: Branch-wise data scoping — Contractors must show only the assigned
//          branch's data for a Branch Head, per the Branch Administration spec.
//          Additive/nullable: existing contractor rows are unaffected (they
//          stay invisible to branch-scoped users until a Super Admin assigns
//          them a branch — never leak cross-branch, and Super Admin/unscoped
//          views are unaffected).
// Author: System
// Date: 2026-07-12

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
