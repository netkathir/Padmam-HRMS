<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organization Profile FSD — fields the existing `company_profile` table
 * (built as "Company Settings") is missing: a real, unique Organization Code
 * (distinct from the existing optional `short_name`), a Communication
 * Address separate from the Registered Address, District (State/City/Pincode
 * already existed), and a Status (Active/Inactive).
 *
 * `code` is added nullable — required is enforced at the validation layer
 * (SettingsController::updateCompany), matching this app's existing
 * convention for fields added after a table already has data (e.g. Branch's
 * own district/contact_person columns). The single pre-existing seeded row
 * is backfilled with a generated code so the unique index has no collision
 * risk, and so the very next edit through the UI (which now requires code)
 * doesn't appear to regress existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profile', function (Blueprint $table) {
            if (! Schema::hasColumn('company_profile', 'code')) {
                $table->string('code', 20)->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('company_profile', 'communication_address')) {
                $table->text('communication_address')->nullable()->after('address');
            }
            if (! Schema::hasColumn('company_profile', 'district')) {
                $table->string('district', 100)->nullable()->after('state');
            }
            if (! Schema::hasColumn('company_profile', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('industry_type');
            }
        });

        // Backfill the existing singleton row so the new unique `code`
        // column is never left blank going forward.
        if (Schema::hasColumn('company_profile', 'code')) {
            \Illuminate\Support\Facades\DB::table('company_profile')
                ->whereNull('code')
                ->update(['code' => 'ORG001']);
        }
    }

    public function down(): void
    {
        Schema::table('company_profile', function (Blueprint $table) {
            foreach (['code', 'communication_address', 'district', 'is_active'] as $column) {
                if (Schema::hasColumn('company_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
