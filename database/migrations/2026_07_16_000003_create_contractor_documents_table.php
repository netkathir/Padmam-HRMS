<?php
// File: database/migrations/2026_07_16_000003_create_contractor_documents_table.php
// Purpose: Module 5 FSD 9.1 — "Document Upload — Agreement, licence and
//          supporting documents." Built as a clean, internally-consistent
//          new table rather than reusing the `employee_documents` pattern,
//          which was found to be broken (model/controller reference columns
//          — document_number, expiry_date, is_verified — that don't exist in
//          that table's migration, and required columns — file_size,
//          file_type, uploaded_by — the controller never sets). Storage
//          convention (public disk, mimes:pdf,jpg,jpeg,png, max:5120) is
//          reused as-is.
// Author: System
// Date: 2026-07-16

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contractor_documents')) {
            return;
        }

        Schema::create('contractor_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('contractor_id');
            $table->enum('document_type', ['agreement', 'licence', 'other'])->default('other');
            $table->string('original_name', 255);
            $table->string('file_path', 255);
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('contractor_id')->references('id')->on('contractors')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index('contractor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_documents');
    }
};
