<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'source_document_disk')) {
                $table->string('source_document_disk', 40)->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('form_templates', 'source_document_path')) {
                $table->string('source_document_path', 500)->nullable()->after('source_document_disk');
            }
            if (! Schema::hasColumn('form_templates', 'source_document_original_name')) {
                $table->string('source_document_original_name', 255)->nullable()->after('source_document_path');
            }
            if (! Schema::hasColumn('form_templates', 'source_document_mime')) {
                $table->string('source_document_mime', 120)->nullable()->after('source_document_original_name');
            }
            if (! Schema::hasColumn('form_templates', 'source_document_size')) {
                $table->unsignedBigInteger('source_document_size')->nullable()->after('source_document_mime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            foreach ([
                'source_document_disk',
                'source_document_path',
                'source_document_original_name',
                'source_document_mime',
                'source_document_size',
            ] as $column) {
                if (Schema::hasColumn('form_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
