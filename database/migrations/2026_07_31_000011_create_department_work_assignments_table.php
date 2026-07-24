<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_work_assignments', function (Blueprint $table) {
            $table->id();
            // employees.id is unsignedInteger (increments()) and
            // departments.id is unsignedSmallInteger (smallIncrements()) —
            // matching those exact column types here, since foreignId()
            // assumes bigInteger and fails with an incompatible-column error.
            $table->unsignedInteger('employee_id');
            $table->unsignedSmallInteger('department_id');
            $table->date('work_date');
            // Snapshotted from the department at the time of entry, so a
            // later change to the department's Value Per Day never
            // retroactively changes an already-recorded day's value.
            $table->decimal('value_per_day', 10, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_work_assignments');
    }
};
