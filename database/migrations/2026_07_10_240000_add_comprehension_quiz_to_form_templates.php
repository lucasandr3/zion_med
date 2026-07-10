<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'comprehension_quiz')) {
                $table->json('comprehension_quiz')->nullable()->after('consent_validity_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'comprehension_quiz')) {
                $table->dropColumn('comprehension_quiz');
            }
        });
    }
};
