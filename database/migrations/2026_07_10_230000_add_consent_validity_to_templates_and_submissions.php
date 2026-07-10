<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'consent_validity_days')) {
                $table->unsignedInteger('consent_validity_days')->nullable()->after('document_kind');
            }
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('form_submissions', 'consent_valid_until')) {
                $table->timestamp('consent_valid_until')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'consent_validity_days')) {
                $table->dropColumn('consent_validity_days');
            }
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'consent_valid_until')) {
                $table->dropColumn('consent_valid_until');
            }
        });
    }
};
