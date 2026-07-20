<?php
// Purpose: Employee-Checkpoint Mapping — prevent duplicate mappings for the
// same (checkpoint, employee) combination. The original unique index was
// (checkpoint_id, emp_checkpoint_id, employee_id) — a 3-column composite
// that allowed the same employee to be mapped to the same checkpoint
// multiple times as long as emp_checkpoint_id differed. That's no longer
// wanted: one employee can only ever have ONE mapping per checkpoint.
// Table affected: employee_checkpoints.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_checkpoints', function (Blueprint $table) {
            $table->dropUnique('emp_checkpoint_unique');
            $table->unique(['checkpoint_id', 'employee_id'], 'employee_checkpoints_checkpoint_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_checkpoints', function (Blueprint $table) {
            $table->dropUnique('employee_checkpoints_checkpoint_employee_unique');
            $table->unique(['checkpoint_id', 'emp_checkpoint_id', 'employee_id'], 'emp_checkpoint_unique');
        });
    }
};
