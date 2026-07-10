<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('form_submissions', 'document_snapshot')) {
                $table->json('document_snapshot')->nullable()->after('document_snapshot_hash');
            }
        });

        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'document_kind')) {
                $table->string('document_kind', 32)
                    ->default('ficha')
                    ->after('category');
            }
        });

        if (Schema::hasColumn('form_templates', 'document_kind')) {
            \Illuminate\Support\Facades\DB::table('form_templates')
                ->where('category', 'consentimento')
                ->update(['document_kind' => 'consentimento']);
        }
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'document_snapshot')) {
                $table->dropColumn('document_snapshot');
            }
        });

        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'document_kind')) {
                $table->dropColumn('document_kind');
            }
        });
    }
};
