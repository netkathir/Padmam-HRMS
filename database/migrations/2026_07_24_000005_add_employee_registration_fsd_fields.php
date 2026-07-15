<?php
// Purpose: Employee Registration FSD — supports the new 10-tab wizard:
// `is_draft` lets "Save as Draft" persist an incomplete registration (full
// validation is only enforced by "Save Employee" on the final tab).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'is_draft')) {
                $table->dropColumn('is_draft');
            }
        });
    }
};
