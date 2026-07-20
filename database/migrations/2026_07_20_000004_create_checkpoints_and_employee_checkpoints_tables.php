<?php
// Purpose: Biometric Bulk Upload rework — "Checkpoint" replaces the old
// free-text "door" concept entirely. Checkpoint Master holds the named
// checkpoints (SPP, SPI, SGI, ...); Employee-Checkpoint Mapping records
// which door-local ID ("emp_checkpoint_id", e.g. "001") an employee is
// registered under at a given checkpoint — an employee can have a
// different ID per checkpoint, or the same one, so this is a genuine
// one-employee-to-many-checkpoints mapping, not a single scalar column.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('code', 20)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkpoint_id')->constrained('checkpoints')->cascadeOnDelete();
            $table->string('emp_checkpoint_id', 50);
            // employees.id is an unsignedInteger (increments()), not
            // bigIncrements — foreignId() would create an incompatible
            // unsignedBigInteger column, so this is declared to match.
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['checkpoint_id', 'emp_checkpoint_id', 'employee_id'], 'emp_checkpoint_unique');
            $table->index(['checkpoint_id', 'emp_checkpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_checkpoints');
        Schema::dropIfExists('checkpoints');
    }
};
