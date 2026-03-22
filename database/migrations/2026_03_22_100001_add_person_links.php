<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('organization_id')->constrained('people')->nullOnDelete();
            $table->index(['organization_id', 'person_id']);
        });

        Schema::table('document_sends', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('organization_id')->constrained('people')->nullOnDelete();
            $table->string('recipient_name', 255)->nullable()->after('recipient_email');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->boolean('public_require_person_link')->default(false)->after('public_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('public_require_person_link');
        });

        Schema::table('document_sends', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn('recipient_name');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
