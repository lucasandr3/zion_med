<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('form_fields', 'clinical_step_kind')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->string('clinical_step_kind', 40)
                    ->nullable()
                    ->after('visibility_rules');
            });
        }

        if (! Schema::hasColumn('form_templates', 'uses_clinical_steps')) {
            Schema::table('form_templates', function (Blueprint $table) {
                $table->boolean('uses_clinical_steps')
                    ->default(false)
                    ->after('actors_visibility_rules');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('form_fields', 'clinical_step_kind')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->dropColumn('clinical_step_kind');
            });
        }

        if (Schema::hasColumn('form_templates', 'uses_clinical_steps')) {
            Schema::table('form_templates', function (Blueprint $table) {
                $table->dropColumn('uses_clinical_steps');
            });
        }
    }
};
