<?php
// File: database/migrations/2026_07_10_000006_create_branch_head_assignments_table.php
// Purpose: Branch Administration — track Branch Head assignment history per branch.
// Author: System
// Date: 2026-07-10

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_head_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('branch_id');
            $table->unsignedInteger('user_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_head_assignments');
    }
};
