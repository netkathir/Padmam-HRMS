<?php
// Purpose: Biometric Bulk Upload — re-uploading a file (or one that overlaps
// a previous upload) now updates already-stored punches in place instead of
// skipping/flagging them as duplicates. This column tracks how many rows in
// a given upload were updates vs. brand-new inserts (valid_rows).
// Table affected: biometric_uploads (adds updated_rows).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_uploads', function (Blueprint $table) {
            $table->unsignedInteger('updated_rows')->default(0)->after('duplicate_rows');
        });
    }

    public function down(): void
    {
        Schema::table('biometric_uploads', function (Blueprint $table) {
            $table->dropColumn('updated_rows');
        });
    }
};
