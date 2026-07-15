<?php
// Purpose: Employee Registration FSD Tab 9 (Salary Structure) — "Salary
// Components must automatically display Component Type, Calculation Type,
// Calculation Base, and Calculated Amount based on the selected Salary
// Component." This is an employee-specific breakdown (distinct from the
// Salary Slab-level "Earnings/Deductions Composition" template feature
// that was removed from Salary Slab Master) tied to one salary structure
// revision, snapshotting the source component's configuration at the time
// it was added so history stays accurate even if the master component is
// later edited.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_salary_structure_id');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->unsignedTinyInteger('component_id');
            $table->string('component_name', 100);
            $table->string('calculation_type', 20); // fixed | percentage | formula | statutory (snapshot of the source component's own type)
            $table->string('calculation_base', 100)->nullable();
            $table->decimal('rate', 8, 2)->nullable(); // percentage or fixed value, per calculation_type
            $table->decimal('calculated_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('employee_salary_structure_id')->references('id')->on('employee_salary_structure')->cascadeOnDelete();
            $table->index('employee_salary_structure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_components');
    }
};
