<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('form_templates', 'print_layout')) {
                $table->json('print_layout')->nullable()->after('source_document_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            if (Schema::hasColumn('form_templates', 'print_layout')) {
                $table->dropColumn('print_layout');
            }
        });
    }
};
