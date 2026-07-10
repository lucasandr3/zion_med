<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'public_person_link_mode')) {
                $table->string('public_person_link_mode', 16)
                    ->nullable()
                    ->default('code')
                    ->after('public_require_person_link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'public_person_link_mode')) {
                $table->dropColumn('public_person_link_mode');
            }
        });
    }
};
