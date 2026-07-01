<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('employee_code', 20)->unique();

            // Organisational
            $table->unsignedSmallInteger('branch_id');
            $table->unsignedSmallInteger('department_id');
            $table->unsignedSmallInteger('designation_id');
            $table->unsignedTinyInteger('employee_type_id');
            $table->unsignedSmallInteger('contractor_id')->nullable();
            $table->unsignedSmallInteger('shift_id')->nullable();
            $table->unsignedInteger('reporting_to')->nullable();
            $table->unsignedTinyInteger('salary_slab_id')->nullable();

            // Personal
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('nationality', 50)->default('Indian');
            $table->string('religion', 50)->nullable();

            // Contact
            $table->string('personal_email', 150)->nullable();
            $table->string('official_email', 150)->nullable()->unique();
            $table->string('phone', 15);
            $table->string('alternate_phone', 15)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 15)->nullable();

            // Address
            $table->string('address_line1', 200)->nullable();
            $table->string('address_line2', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            // Job
            $table->date('date_of_joining');
            $table->date('date_of_confirmation')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated', 'on_leave', 'probation'])->default('probation');

            // Government IDs
            $table->string('aadhaar_number', 20)->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('uan_number', 20)->nullable();
            $table->string('esi_number', 20)->nullable();
            $table->string('passport_number', 20)->nullable();
            $table->date('passport_expiry')->nullable();

            // Photo
            $table->string('profile_photo', 255)->nullable();

            // Payroll flags
            $table->boolean('is_pf_applicable')->default(true);
            $table->boolean('is_esi_applicable')->default(true);
            $table->boolean('is_tds_applicable')->default(false);

            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'department_id']);
            $table->index('status');
            $table->index('date_of_joining');
            $table->index('reporting_to');

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('designation_id')->references('id')->on('designations');
            $table->foreign('employee_type_id')->references('id')->on('employee_types');
            $table->foreign('contractor_id')->references('id')->on('contractors')->onDelete('set null');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            $table->foreign('reporting_to')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->onDelete('set null');
        });

        // Back-reference FKs
        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_id')->references('id')->on('employees')->onDelete('set null');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
        });

        // Employee sub-tables
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->enum('document_type', [
                'aadhaar', 'pan', 'passport', 'offer_letter', 'resume',
                'relieving_letter', 'experience_letter', 'education_certificate',
                'photo', 'photo_id', 'bank_proof', 'other',
            ]);
            $table->string('document_name', 200);
            $table->string('file_path', 255);
            $table->unsignedInteger('file_size')->comment('bytes');
            $table->string('file_type', 50)->comment('MIME type');
            $table->string('remarks', 255)->nullable();
            $table->unsignedInteger('uploaded_by');
            $table->timestamps();
            $table->softDeletes();
            $table->index('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->string('account_holder_name', 100);
            $table->string('bank_name', 100);
            $table->string('branch_name', 100)->nullable();
            $table->string('account_number', 50);
            $table->string('ifsc_code', 20);
            $table->enum('account_type', ['savings', 'current', 'salary'])->default('savings');
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
            $table->index('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('employee_salary_structure', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedTinyInteger('salary_slab_id')->nullable();
            $table->decimal('ctc', 12, 2);
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('da', 12, 2)->default(0);
            $table->decimal('ta', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('special_allowance', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('pf_employee', 12, 2)->default(0);
            $table->decimal('pf_employer', 12, 2)->default(0);
            $table->decimal('esi_employee', 12, 2)->default(0);
            $table->decimal('esi_employer', 12, 2)->default(0);
            $table->decimal('tds', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('employee_id');
            $table->index(['employee_id', 'is_current']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedSmallInteger('shift_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->unsignedInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'is_current']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('employee_exits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id')->unique();
            $table->enum('exit_type', ['resignation', 'termination', 'retirement', 'absconding', 'death', 'other']);
            $table->date('notice_date')->nullable();
            $table->date('last_working_date');
            $table->date('exit_date');
            $table->unsignedSmallInteger('notice_period_days')->default(0);
            $table->unsignedSmallInteger('notice_period_served')->default(0);
            $table->text('reason')->nullable();
            $table->enum('full_and_final_status', ['pending', 'processed', 'paid'])->default('pending');
            $table->decimal('fnf_amount', 12, 2)->nullable();
            $table->date('fnf_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->timestamps();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_exits');
        Schema::dropIfExists('employee_shift_assignments');
        Schema::dropIfExists('employee_salary_structure');
        Schema::dropIfExists('employee_bank_details');
        Schema::dropIfExists('employee_documents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_id']);
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::dropIfExists('employees');
    }
};
