<?php
// Purpose: Salary Slab Master FSD revision — a salary slab no longer has a
// manually-entered Name (auto-generated from From/To Salary instead), a
// Branch (uses the active branch context app-wide, not a per-slab field),
// or an Earnings/Deductions Composition (never consumed by payroll — see
// exploration this session confirming PayrollController computes gross pay
// from EmployeeSalaryStructure, not SalarySlab->components).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropUnique('salary_slabs_name_unique');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('salary_slab_components');
    }

    public function down(): void
    {
        Schema::create('salary_slab_components', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedTinyInteger('salary_slab_id');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->unsignedTinyInteger('component_id');
            $table->enum('value_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->index('salary_slab_id');
            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->onDelete('cascade');
        });

        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('applicable_employee_types')
                ->comment('NULL = all branches');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->unique('name');
        });
    }
};
