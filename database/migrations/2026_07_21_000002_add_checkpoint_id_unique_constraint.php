<?php
// Purpose: Employee-Checkpoint Mapping — a door-local Employee Checkpoint ID
// must be unique WITHIN a checkpoint (the biometric device can't tell two
// employees apart if both are registered as, say, "200" at the same
// checkpoint). Combined with the earlier (checkpoint_id, employee_id)
// unique constraint, this gives the two independent rules actually needed:
// one employee -> at most one mapping per checkpoint, AND one ID -> at most
// one employee per checkpoint. Different checkpoints may freely reuse the
// same ID for different people. Table affected: employee_checkpoints.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_checkpoints', function (Blueprint $table) {
            $table->dropIndex('employee_checkpoints_checkpoint_id_emp_checkpoint_id_index');
            $table->unique(['checkpoint_id', 'emp_checkpoint_id'], 'employee_checkpoints_checkpoint_empid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_checkpoints', function (Blueprint $table) {
            $table->dropUnique('employee_checkpoints_checkpoint_empid_unique');
            $table->index(['checkpoint_id', 'emp_checkpoint_id'], 'employee_checkpoints_checkpoint_id_emp_checkpoint_id_index');
        });
    }
};
