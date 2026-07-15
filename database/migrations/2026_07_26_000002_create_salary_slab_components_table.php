<?php
// Purpose: Salary Slab Master — "single source of truth" for employee
// salary. Reinstates a slab-level Earnings/Deductions breakdown (a
// conceptually similar feature existed once as `salary_slab_components` and
// was removed since nothing consumed it — this is a deliberate, richer
// re-introduction now that the Employee module is being changed to
// literally inherit its whole salary structure from the selected slab).
// Column shape mirrors the already-proven `employee_salary_components`
// table (the per-employee snapshot this feeds into) for consistency.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slab_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('salary_slab_id');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->unsignedTinyInteger('component_id');
            $table->string('component_name', 100);
            $table->string('calculation_type', 20); // fixed | percentage | formula | statutory (snapshot of the source component's own type)
            $table->string('calculation_base', 100)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('calculated_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->cascadeOnDelete();
            $table->index('salary_slab_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slab_components');
    }
};
