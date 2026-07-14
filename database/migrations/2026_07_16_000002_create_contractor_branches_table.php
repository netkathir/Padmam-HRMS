<?php
// File: database/migrations/2026_07_16_000002_create_contractor_branches_table.php
// Purpose: Module 5 FSD 9.1 — "Branch Applicability — Multi-select — One or
//          more branches" / "Contractors may be associated with multiple
//          branches." The existing single `contractors.branch_id` column is
//          untouched and keeps driving BranchScope exactly as today (the
//          contractor's primary/owning branch for access control); this
//          pivot is the additive multi-select of every branch the
//          contractor's labour may operate across. Mirrors the
//          `shift_branches` pivot convention from Module 3.
// Author: System
// Date: 2026-07-16

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contractor_branches')) {
            return;
        }

        Schema::create('contractor_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('contractor_id');
            $table->unsignedSmallInteger('branch_id');
            $table->timestamps();

            $table->foreign('contractor_id')->references('id')->on('contractors')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['contractor_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_branches');
    }
};
