<?php
// Purpose: employees.official_email was validated at the app layer with no
// branch scoping at all (unlike its siblings employee_code/biometric_number
// in the same rules array), and the DB itself still enforced a single-column
// global unique index. Moves uniqueness to the (branch_id, official_email)
// pair, matching how employee_code/biometric_number already work.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['official_email']);
            $table->unique(['branch_id', 'official_email']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'official_email']);
            $table->unique(['official_email']);
        });
    }
};
