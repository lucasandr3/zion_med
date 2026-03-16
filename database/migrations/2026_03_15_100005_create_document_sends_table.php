<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone', 50)->nullable();
            $table->string('channel', 20); // email, whatsapp
            $table->timestamp('sent_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('form_submission_id')->nullable()->constrained('form_submissions')->nullOnDelete();
            $table->string('public_token', 64)->nullable(); // link token used
            $table->timestamps();
        });

        Schema::table('document_sends', function (Blueprint $table) {
            $table->index(['organization_id', 'channel']);
            $table->index(['form_template_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sends');
    }
};
