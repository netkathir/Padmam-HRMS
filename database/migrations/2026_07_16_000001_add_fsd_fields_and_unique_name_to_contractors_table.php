<?php
// File: database/migrations/2026_07_16_000001_add_fsd_fields_and_unique_name_to_contractors_table.php
// Purpose: Module 5 FSD 9.1 — Contractor Master requires alternate phone,
//          state, district, PIN code, PAN number, PF/ESI registration
//          numbers, agreement start/end dates, max labour count, and a
//          unique Contractor Name. New columns stay nullable at the DB
//          level (no uniqueness requirement on them, unlike `code`) —
//          "mandatory" is enforced at the validation layer going forward
//          rather than backfilling unknown real values into existing
//          production rows. `name` gets a real unique index, with existing
//          duplicates disambiguated first so this is safe against live data.
// Author: System
// Date: 2026-07-16

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            if (! Schema::hasColumn('contractors', 'alternate_phone')) {
                $table->string('alternate_phone', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('contractors', 'state')) {
                $table->string('state', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('contractors', 'district')) {
                $table->string('district', 100)->nullable()->after('state');
            }
            if (! Schema::hasColumn('contractors', 'pincode')) {
                $table->string('pincode', 10)->nullable()->after('district');
            }
            if (! Schema::hasColumn('contractors', 'pan_number')) {
                $table->string('pan_number', 20)->nullable()->after('gst_number');
            }
            if (! Schema::hasColumn('contractors', 'pf_registration_number')) {
                $table->string('pf_registration_number', 50)->nullable()->after('pan_number');
            }
            if (! Schema::hasColumn('contractors', 'esi_registration_number')) {
                $table->string('esi_registration_number', 50)->nullable()->after('pf_registration_number');
            }
            if (! Schema::hasColumn('contractors', 'agreement_start_date')) {
                $table->date('agreement_start_date')->nullable()->after('license_expiry');
            }
            if (! Schema::hasColumn('contractors', 'agreement_end_date')) {
                $table->date('agreement_end_date')->nullable()->after('agreement_start_date');
            }
            if (! Schema::hasColumn('contractors', 'max_labour_count')) {
                $table->unsignedInteger('max_labour_count')->nullable()->after('agreement_end_date');
            }
        });

        // De-duplicate any existing duplicate names before the unique index
        // is added (same derived-table self-join technique used for
        // departments/salary_slabs in Module 3).
        $rows = DB::table('contractors')->select('id', 'name')->orderBy('id')->get();
        $seen = [];
        foreach ($rows as $row) {
            $name = $row->name;
            if (isset($seen[$name])) {
                $name = $name . ' (' . $row->id . ')';
                DB::table('contractors')->where('id', $row->id)->update(['name' => $name]);
            }
            $seen[$name] = true;
        }

        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexes = collect(Schema::getIndexes('contractors'))->pluck('name');
        if (! $indexes->contains('contractors_name_unique')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropUnique('contractors_name_unique');
            foreach ([
                'max_labour_count', 'agreement_end_date', 'agreement_start_date',
                'esi_registration_number', 'pf_registration_number', 'pan_number',
                'pincode', 'district', 'state', 'alternate_phone',
            ] as $col) {
                if (Schema::hasColumn('contractors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
