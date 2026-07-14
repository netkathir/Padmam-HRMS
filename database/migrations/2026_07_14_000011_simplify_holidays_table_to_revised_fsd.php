<?php
// Purpose: Holiday Master FSD revision — Holiday is now a single,
// branch-agnostic company-wide calendar defined by only: name, a date range
// (start_date/end_date, replacing the old single `date`), applicable
// employee types, paid/unpaid, and active/inactive. Calendar Name, Type,
// Branch, and Description are no longer part of the module and are dropped.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            if (! Schema::hasColumn('holidays', 'start_date')) {
                $table->date('start_date')->nullable()->after('name');
            }
            if (! Schema::hasColumn('holidays', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });

        DB::table('holidays')->whereNull('start_date')->update([
            'start_date' => DB::raw('`date`'),
            'end_date'   => DB::raw('`date`'),
        ]);

        Schema::table('holidays', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['year', 'branch_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'calendar_name', 'type', 'description', 'year', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id')->comment('NULL = all branches');
            $table->string('calendar_name', 150)->nullable()->after('branch_id');
            $table->enum('type', ['public_holiday', 'festival_holiday', 'optional', 'company_holiday'])->default('public_holiday')->after('start_date');
            $table->text('description')->nullable()->after('name');
            $table->year('year')->nullable()->after('end_date');
            $table->date('date')->nullable()->after('name');
        });

        DB::table('holidays')->update(['date' => DB::raw('`start_date`')]);
        DB::table('holidays')->update(['year' => DB::raw('YEAR(`start_date`)')]);

        Schema::table('holidays', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->year('year')->nullable(false)->change();
            $table->index('date');
            $table->index(['year', 'branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
