<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('username', 50)->unique();
            $table->string('password', 255);
            $table->unsignedTinyInteger('role_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('role_id');
            $table->index('employee_id');
            $table->foreign('role_id')->references('id')->on('roles');
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('token', 100);
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('token');
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('token_blacklist', function (Blueprint $table) {
            $table->increments('id');
            $table->string('jti', 50)->unique();
            $table->unsignedInteger('user_id');
            $table->dateTime('expires_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_blacklist');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');
    }
};
