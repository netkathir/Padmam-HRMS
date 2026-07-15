<?php
// Purpose: Contractor Master FSD revision — removes the Branch concept from
// Contractor entirely (both the single "Primary Branch" branch_id column and
// the "Applicable Branches" contractor_branches pivot table). Contractor
// becomes a single, global master — not scoped to any branch. Also drops
// the unused "Company Name" field.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'company_name']);
        });

        Schema::dropIfExists('contractor_branches');
    }

    public function down(): void
    {
        Schema::create('contractor_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('contractor_id');
            $table->unsignedSmallInteger('branch_id');
            $table->timestamps();

            $table->foreign('contractor_id')->references('id')->on('contractors')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['contractor_id', 'branch_id']);
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->string('company_name', 150)->nullable()->after('name');
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }
};
