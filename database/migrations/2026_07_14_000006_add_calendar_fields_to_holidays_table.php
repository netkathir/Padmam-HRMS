<?php
// File: database/migrations/2026_07_14_000006_add_calendar_fields_to_holidays_table.php
// Purpose: Module 3 FSD 7.3 — Holiday Calendar requires a Calendar Name (unique
//          per year+branch), Applicable Employee Type (multi-select), a Paid
//          Holiday flag, a real Description column (the model already claimed
//          one in `fillable` that never existed in the DB — dead/broken field),
//          and Holiday Type values of Public/Festival/Optional/Company (today:
//          national/regional/optional). Existing rows are backfilled so this
//          is safe against live holiday data.
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            if (! Schema::hasColumn('holidays', 'calendar_name')) {
                $table->string('calendar_name', 150)->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('holidays', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('holidays', 'is_paid')) {
                $table->boolean('is_paid')->default(true)->after('type');
            }
            if (! Schema::hasColumn('holidays', 'applicable_employee_types')) {
                $table->json('applicable_employee_types')->nullable()->after('is_paid');
            }
            if (! Schema::hasColumn('holidays', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Backfill calendar_name for existing rows so the field is populated
        // going forward without breaking historical records.
        $rows = DB::table('holidays')
            ->select('id', 'year', 'branch_id')
            ->whereNull('calendar_name')
            ->get();
        $branchNames = DB::table('branches')->pluck('name', 'id');
        foreach ($rows as $row) {
            $label = $row->branch_id ? ($branchNames[$row->branch_id] ?? 'Branch') : 'All Branches';
            DB::table('holidays')->where('id', $row->id)->update([
                'calendar_name' => "{$label} Holidays {$row->year}",
            ]);
        }

        // Remap type enum values to the FSD's 4-value set before altering
        // the column (national->public_holiday, regional->festival_holiday,
        // optional stays, company_holiday is new).
        if (Schema::hasColumn('holidays', 'type')) {
            DB::statement("ALTER TABLE holidays MODIFY type VARCHAR(30) NOT NULL DEFAULT 'public_holiday'");
            DB::table('holidays')->where('type', 'national')->update(['type' => 'public_holiday']);
            DB::table('holidays')->where('type', 'regional')->update(['type' => 'festival_holiday']);
            DB::statement("ALTER TABLE holidays MODIFY type ENUM('public_holiday','festival_holiday','optional','company_holiday') NOT NULL DEFAULT 'public_holiday'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('holidays', 'type')) {
            DB::statement("ALTER TABLE holidays MODIFY type VARCHAR(30) NOT NULL DEFAULT 'national'");
            DB::table('holidays')->where('type', 'public_holiday')->update(['type' => 'national']);
            DB::table('holidays')->where('type', 'festival_holiday')->update(['type' => 'regional']);
            DB::table('holidays')->where('type', 'company_holiday')->update(['type' => 'optional']);
            DB::statement("ALTER TABLE holidays MODIFY type ENUM('national','regional','optional') NOT NULL DEFAULT 'national'");
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('holidays', 'applicable_employee_types')) {
                $table->dropColumn('applicable_employee_types');
            }
            if (Schema::hasColumn('holidays', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
            if (Schema::hasColumn('holidays', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('holidays', 'calendar_name')) {
                $table->dropColumn('calendar_name');
            }
        });
    }
};
