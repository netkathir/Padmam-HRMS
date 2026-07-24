<?php
// Purpose: Leave Type Master FSD revision — a leave type is now defined by
// only: name (unique), code (unique), applicable employee types, paid/unpaid,
// and active/inactive. days_per_year, max_carry_forward, is_carry_forward,
// is_half_day_allowed, gender_specific, requires_document, min_notice_days,
// and max_consecutive_days are no longer part of the module and are dropped.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! $this->hasUniqueIndex('leave_types', 'leave_types_name_unique')) {
                $table->unique('name');
            }
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'days_per_year', 'max_carry_forward', 'is_carry_forward',
                'is_half_day_allowed', 'gender_specific', 'requires_document',
                'min_notice_days', 'max_consecutive_days',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->decimal('days_per_year', 5, 2)->default(0)->after('code');
            $table->decimal('max_carry_forward', 5, 2)->default(0)->after('days_per_year');
            $table->boolean('is_carry_forward')->default(false)->after('max_carry_forward');
            $table->boolean('is_half_day_allowed')->default(true)->after('is_paid');
            $table->enum('gender_specific', ['all', 'male', 'female'])->default('all')->after('is_half_day_allowed');
            $table->boolean('requires_document')->default(false)->after('applicable_employee_types');
            $table->unsignedTinyInteger('min_notice_days')->default(0)->after('requires_document');
            $table->unsignedTinyInteger('max_consecutive_days')->nullable()->after('min_notice_days');
            $table->dropUnique('leave_types_name_unique');
        });
    }

    private function hasUniqueIndex(string $table, string $indexName): bool
    {
        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        return collect(Schema::getIndexes($table))->pluck('name')->contains($indexName);
    }
};
