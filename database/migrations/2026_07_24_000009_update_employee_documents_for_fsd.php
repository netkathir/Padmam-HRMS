<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FSD Tab 10 (Employee Documents) — adds Issue Date (the Document Type/
 * Number/Expiry/File/Remarks fields already existed) and replaces the
 * document_type enum with the FSD's own value set. Existing rows whose
 * document_type no longer exists in the new set are remapped to the closest
 * equivalent (or "other") before the column is redefined, so the ALTER can
 * never fail on data already in the table.
 */
return new class extends Migration
{
    private const OLD_TO_NEW = [
        'offer_letter'      => 'appointment_letter',
        'relieving_letter'  => 'employment_agreement',
        'photo'             => 'other',
        'photo_id'          => 'other',
        'resume'            => 'other',
        // aadhaar, pan, passport, bank_proof, education_certificate, other:
        // already valid in the new set, no remap needed.
        'experience_letter' => 'experience_certificate',
    ];

    public function up(): void
    {
        if (Schema::hasTable('employee_documents')) {
            foreach (self::OLD_TO_NEW as $old => $new) {
                DB::table('employee_documents')->where('document_type', $old)->update(['document_type' => $new]);
            }
        }

        DB::statement("ALTER TABLE employee_documents MODIFY document_type ENUM('aadhaar','pan','bank_proof','appointment_letter','employment_agreement','education_certificate','experience_certificate','contractor_id','passport','other') NOT NULL");

        if (! Schema::hasColumn('employee_documents', 'issue_date')) {
            Schema::table('employee_documents', function ($table) {
                $table->date('issue_date')->nullable()->after('document_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_documents', 'issue_date')) {
            Schema::table('employee_documents', function ($table) {
                $table->dropColumn('issue_date');
            });
        }

        DB::statement("ALTER TABLE employee_documents MODIFY document_type ENUM('aadhaar','pan','passport','offer_letter','resume','relieving_letter','experience_letter','education_certificate','photo','photo_id','bank_proof','other') NOT NULL");
    }
};
