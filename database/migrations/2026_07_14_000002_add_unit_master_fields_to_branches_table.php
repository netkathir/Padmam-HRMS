<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch/Unit Master FSD — fields missing from the existing `branches`
 * table: Unit Type, a Closure Date (paired with the existing `start_date`
 * for the "start cannot be after closure" rule), branch-specific PF/ESI
 * registration numbers (distinct from the org-wide ones on company_profile),
 * a lightweight Weekly Off Rule (no "Rule Engine" exists anywhere in this
 * app to reference, so this is a plain per-branch field, not a new rule
 * subsystem), and a real DB-level unique constraint on `name` (previously
 * enforced only at the application-validation layer).
 *
 * Verified no duplicate branch names exist in the live data before adding
 * the unique index (2 branches total, no collisions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('unit_type', 50)->nullable()->after('code');
            $table->date('closure_date')->nullable()->after('start_date');
            $table->string('pf_establishment_number', 50)->nullable()->after('closure_date');
            $table->string('esi_employer_code', 50)->nullable()->after('pf_establishment_number');
            $table->json('weekly_off_days')->nullable()->after('esi_employer_code');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['unit_type', 'closure_date', 'pf_establishment_number', 'esi_employer_code', 'weekly_off_days']);
        });
    }
};
