<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'library_key')) {
                $table->string('library_key', 160)->nullable()->after('category');
                $table->unsignedSmallInteger('library_content_version')->nullable()->after('library_key');
                $table->string('legal_review_status', 20)->nullable()->after('library_content_version');
                $table->string('clinical_review_status', 20)->nullable()->after('legal_review_status');
                $table->timestamp('library_reviewed_at')->nullable()->after('clinical_review_status');
                $table->index(['organization_id', 'library_key'], 'form_templates_org_library_key_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'library_key')) {
                $table->dropIndex('form_templates_org_library_key_idx');
                $table->dropColumn([
                    'library_key',
                    'library_content_version',
                    'legal_review_status',
                    'clinical_review_status',
                    'library_reviewed_at',
                ]);
            }
        });
    }
};
