<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('patient_download_token', 64)->nullable()->unique()->after('document_snapshot');
            $table->timestamp('patient_download_token_expires_at')->nullable()->after('patient_download_token');
            $table->timestamp('patient_copy_downloaded_at')->nullable()->after('patient_download_token_expires_at');
            $table->timestamp('patient_copy_emailed_at')->nullable()->after('patient_copy_downloaded_at');
            $table->string('pdf_disk_path')->nullable()->after('patient_copy_emailed_at');
            $table->string('pdf_sha256', 64)->nullable()->after('pdf_disk_path');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_sha256');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'patient_download_token',
                'patient_download_token_expires_at',
                'patient_copy_downloaded_at',
                'patient_copy_emailed_at',
                'pdf_disk_path',
                'pdf_sha256',
                'pdf_generated_at',
            ]);
        });
    }
};
