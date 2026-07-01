<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->date('date');
            $table->unsignedSmallInteger('shift_id')->nullable();
            $table->dateTime('in_time')->nullable();
            $table->dateTime('out_time')->nullable();
            $table->unsignedSmallInteger('work_minutes')->default(0);
            $table->unsignedSmallInteger('ot_minutes')->default(0);
            $table->enum('status', ['present','absent','half_day','holiday','weekend','on_leave','late','early_exit'])->default('absent');
            $table->boolean('is_late')->default(false);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->boolean('is_early_exit')->default(false);
            $table->unsignedSmallInteger('early_exit_minutes')->default(0);
            $table->enum('source', ['web','mobile','biometric','manual'])->default('manual');
            $table->boolean('is_manual_entry')->default(false);
            $table->enum('approval_status', ['pending','approved','rejected'])->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('status');
            $table->index('approval_status');
            $table->index('is_late');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');
            $table->string('employee_code', 20);
            $table->string('device_id', 50)->nullable();
            $table->dateTime('punch_time');
            $table->enum('punch_type', ['in','out','break_in','break_out','unknown'])->default('unknown');
            $table->enum('source', ['biometric','access_card','mobile','web'])->default('biometric');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->unsignedInteger('attendance_id')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['employee_id', 'punch_time']);
            $table->index('punch_time');
            $table->index('is_processed');
            $table->index('device_id');

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('attendance_id')->references('id')->on('attendance')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('attendance');
    }
};
