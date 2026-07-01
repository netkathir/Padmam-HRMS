<?php
/**
 * File: database/migrations/2026_07_01_000001_create_contract_workers_tables.php
 * Purpose: Add company_name/gst_number to contractors; create contract_workers,
 *          contract_worker_attendance, and contract_worker_payroll tables.
 * Author: System
 * Date: 2026-07-01
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->string('company_name', 150)->nullable()->after('name');
            $table->string('gst_number', 20)->nullable()->after('license_number');
        });

        Schema::create('contract_workers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('contractor_id');
            $table->string('name', 100);
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('id_proof_type', ['aadhaar', 'passport', 'voter_id', 'driving_license', 'other'])->nullable();
            $table->string('id_proof_number', 50)->nullable();
            $table->string('skill_type', 100)->nullable();
            $table->enum('wage_type', ['daily', 'monthly'])->default('daily');
            $table->decimal('wage_amount', 10, 2)->default(0);
            $table->date('joining_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('contractor_id');
            $table->index('status');
            $table->foreign('contractor_id')->references('id')->on('contractors')->onDelete('cascade');
        });

        Schema::create('contract_worker_attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_worker_id');
            $table->unsignedSmallInteger('contractor_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'half_day'])->default('absent');
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->decimal('ot_hours', 4, 2)->default(0);
            $table->string('remarks', 255)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['contract_worker_id', 'date']);
            $table->index('date');
            $table->index(['contractor_id', 'date']);
            $table->foreign('contract_worker_id')->references('id')->on('contract_workers')->onDelete('cascade');
            $table->foreign('contractor_id')->references('id')->on('contractors');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('contract_worker_payroll', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('contractor_id');
            $table->unsignedInteger('contract_worker_id');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('total_days')->default(0);
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('half_days', 5, 2)->default(0);
            $table->decimal('ot_hours', 5, 2)->default(0);
            $table->enum('wage_type', ['daily', 'monthly'])->default('daily');
            $table->decimal('wage_amount', 10, 2)->default(0);
            $table->decimal('total_wages', 10, 2)->default(0);
            $table->decimal('ot_amount', 10, 2)->default(0);
            $table->decimal('gross_wages', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_wages', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('payment_remarks')->nullable();
            $table->unsignedInteger('generated_by')->nullable();
            $table->timestamps();

            $table->unique(['contract_worker_id', 'month', 'year']);
            $table->index(['contractor_id', 'month', 'year']);
            $table->foreign('contractor_id')->references('id')->on('contractors');
            $table->foreign('contract_worker_id')->references('id')->on('contract_workers')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_worker_payroll');
        Schema::dropIfExists('contract_worker_attendance');
        Schema::dropIfExists('contract_workers');

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'gst_number']);
        });
    }
};
