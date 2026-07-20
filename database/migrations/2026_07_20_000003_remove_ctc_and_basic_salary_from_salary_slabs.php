<?php
// Purpose: Salary Slab Master simplification — a Salary Slab is now only a
// named PF/ESI/TDS percentage configuration (Slab Name entered manually,
// no auto-generation). Salary range (min_ctc/max_ctc), Basic Salary, and the
// Salary Components breakdown move OFF the Salary Slab master entirely —
// Basic Salary is now entered per-employee on the Employee Slab's
// Designation & Salary section instead (see employee_salary_structure,
// which already carries its own basic_salary column).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('salary_slab_components');

        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropColumn(['min_ctc', 'max_ctc', 'basic_salary']);
        });
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->decimal('min_ctc', 12, 2)->nullable()->after('name');
            $table->decimal('max_ctc', 12, 2)->nullable()->after('min_ctc');
            $table->decimal('basic_salary', 12, 2)->nullable()->after('max_ctc');
        });

        Schema::create('salary_slab_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('salary_slab_id');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->unsignedTinyInteger('component_id');
            $table->string('component_name', 100);
            $table->string('calculation_type', 20);
            $table->string('calculation_base', 100)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('calculated_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->cascadeOnDelete();
            $table->index('salary_slab_id');
        });
    }
};
