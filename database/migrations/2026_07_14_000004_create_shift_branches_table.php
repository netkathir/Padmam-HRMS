<?php
// File: database/migrations/2026_07_14_000004_create_shift_branches_table.php
// Purpose: Module 3 FSD 7.2 — Shift Master requires Branch Applicability as a
//          multi-select ("one or more branches"), unlike Department/Holiday's
//          single branch_id. Mirrors the `user_branches` pivot convention
//          exactly (2026_07_13_000003_create_user_branches_table.php).
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shift_branches')) {
            return;
        }

        Schema::create('shift_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('shift_id');
            $table->unsignedSmallInteger('branch_id');
            $table->timestamps();

            $table->foreign('shift_id')->references('id')->on('shifts')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['shift_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_branches');
    }
};
