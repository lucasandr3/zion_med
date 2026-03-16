<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_signatures', function (Blueprint $table) {
            $table->foreignId('form_template_version_id')->nullable()->after('field_key')->constrained('form_template_versions')->nullOnDelete();
            $table->string('document_hash', 64)->nullable()->after('form_template_version_id');
            $table->string('evidence_hash', 64)->nullable()->after('signed_hash'); // hash of full evidence package
            $table->string('channel', 20)->default('web')->after('evidence_hash');
            $table->string('status', 20)->default('completed')->after('channel');
            $table->timestamp('accepted_text_at')->nullable()->after('status');
            $table->string('locale', 10)->nullable()->after('accepted_text_at');
            $table->string('timezone', 50)->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('submission_signatures', function (Blueprint $table) {
            $table->dropForeign(['form_template_version_id']);
            $table->dropColumn([
                'form_template_version_id',
                'document_hash',
                'evidence_hash',
                'channel',
                'status',
                'accepted_text_at',
                'locale',
                'timezone',
            ]);
        });
    }
};
