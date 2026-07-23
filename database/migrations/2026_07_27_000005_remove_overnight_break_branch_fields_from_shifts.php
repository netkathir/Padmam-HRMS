<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shift_branches');

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['is_overnight', 'break_minutes']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_overnight')->default(false);
            $table->unsignedSmallInteger('break_minutes')->nullable();
        });

        Schema::create('shift_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shift_id', 'branch_id']);
        });
    }
};
