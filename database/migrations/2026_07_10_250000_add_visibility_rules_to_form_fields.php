<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->json('visibility_rules')->nullable()->after('options_json');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->json('actors_visibility_rules')->nullable()->after('comprehension_quiz');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('visibility_rules');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('actors_visibility_rules');
        });
    }
};
