<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['string','integer','boolean','json','date'])->default('string');
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('company_profile', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 200);
            $table->string('short_name', 50)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('tan', 20)->nullable();
            $table->string('cin', 25)->nullable();
            $table->string('pf_registration', 50)->nullable();
            $table->string('esi_registration', 50)->nullable();
            $table->string('pt_registration', 50)->nullable();
            $table->string('industry_type', 100)->nullable();
            $table->unsignedTinyInteger('financial_year_start')->default(4)->comment('Month: 4=April');
            $table->timestamps();
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('event', 100)->unique();
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->json('recipients')->nullable();
            $table->text('email_template')->nullable();
            $table->string('sms_template', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('saved_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('module', 50);
            $table->json('filters');
            $table->json('columns');
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('created_by');
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
            $table->index('module');
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 50)->comment('create|update|delete|login|logout');
            $table->string('table_name', 100);
            $table->string('record_id', 50)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id');
            $table->index(['table_name', 'record_id']);
            $table->index('created_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('company_profile');
        Schema::dropIfExists('settings');
    }
};
