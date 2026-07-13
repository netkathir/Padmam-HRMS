<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard FSD — Employee Type / Labour Type filters and KPIs (Staff Count,
 * Company Labour Count, Contract Labour Count) need a governed classification
 * on Employee, distinct from the free-text `employee_type_id` master table.
 *
 * Schema::hasColumn() guards make this safe to run against an environment
 * that already has these columns (this app's local dev database already
 * carries them, from an earlier migration that was run but never committed
 * to git) as well as a clean database that doesn't.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'primary_employee_type')) {
                $table->enum('primary_employee_type', ['staff', 'labour'])
                    ->default('staff')
                    ->after('employee_type_id');
            }
            if (! Schema::hasColumn('employees', 'labour_type')) {
                $table->enum('labour_type', ['company_labour', 'contract_labour'])
                    ->nullable()
                    ->after('primary_employee_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'labour_type')) {
                $table->dropColumn('labour_type');
            }
            if (Schema::hasColumn('employees', 'primary_employee_type')) {
                $table->dropColumn('primary_employee_type');
            }
        });
    }
};
