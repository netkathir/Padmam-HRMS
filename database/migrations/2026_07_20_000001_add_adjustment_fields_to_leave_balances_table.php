<?php
// File: database/migrations/2026_07_20_000001_add_adjustment_fields_to_leave_balances_table.php
// Purpose: Module 8 FSD 12.3 — "Opening Balance" and "Adjusted Leave" have no
//          existing column on `leave_balances` (existing columns already
//          cover Accrued/Carry Forward/Used/Lapsed). Also adds the
//          `leave_balance_adjustments` audit table ("adjustment history
//          shall be maintained") — a pure append-only log, mirroring how
//          AttendanceLog already works as an audit trail in this codebase.
// Author: System
// Date: 2026-07-20

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_balances', 'opening_balance')) {
                $table->decimal('opening_balance', 5, 2)->default(0)->after('leave_type_id');
            }
            if (! Schema::hasColumn('leave_balances', 'adjusted_days')) {
                $table->decimal('adjusted_days', 5, 2)->default(0)->after('carry_forward_days');
            }
        });

        if (! Schema::hasTable('leave_balance_adjustments')) {
            Schema::create('leave_balance_adjustments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('leave_balance_id');
                $table->unsignedInteger('employee_id');
                $table->unsignedTinyInteger('leave_type_id');
                $table->decimal('adjustment_days', 5, 2);
                $table->text('reason');
                $table->unsignedInteger('adjusted_by');
                $table->timestamp('created_at')->nullable();

                $table->index(['leave_balance_id']);
                $table->index(['employee_id']);

                $table->foreign('leave_balance_id')->references('id')->on('leave_balances')->onDelete('cascade');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('leave_type_id')->references('id')->on('leave_types');
                $table->foreign('adjusted_by')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');

        Schema::table('leave_balances', function (Blueprint $table) {
            foreach (['adjusted_days', 'opening_balance'] as $col) {
                if (Schema::hasColumn('leave_balances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
