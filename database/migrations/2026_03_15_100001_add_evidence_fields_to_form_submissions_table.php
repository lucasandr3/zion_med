<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('template_version_id')->nullable()->after('template_id')->constrained('form_template_versions')->nullOnDelete();
            $table->string('document_hash', 64)->nullable()->after('protocol_number');
            $table->string('document_snapshot_hash', 64)->nullable()->after('document_hash');
            $table->string('signing_channel', 20)->default('web')->after('document_snapshot_hash'); // web, email, whatsapp
            $table->string('signing_status', 20)->default('completed')->after('signing_channel'); // initiated, viewed, accepted, signed, completed
            $table->string('locale', 10)->nullable()->after('signing_status');
            $table->string('timezone', 50)->nullable()->after('locale');
            $table->timestamp('accepted_text_at')->nullable()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropForeign(['template_version_id']);
            $table->dropColumn([
                'template_version_id',
                'document_hash',
                'document_snapshot_hash',
                'signing_channel',
                'signing_status',
                'locale',
                'timezone',
                'accepted_text_at',
            ]);
        });
    }
};
