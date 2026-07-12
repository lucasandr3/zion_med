<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedTinyInteger('protocol_retention_years')->nullable()->after('data_retention_years');
            $table->string('protocol_retention_mode', 20)->default('anonymize')->after('protocol_retention_years');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->timestamp('retention_anonymized_at')->nullable()->after('revoke_reason');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['protocol_retention_years', 'protocol_retention_mode']);
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('retention_anonymized_at');
        });
    }
};
