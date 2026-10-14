<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('status', 32)->default('queued')->index(); // queued|processing|completed|failed|committed
            $table->string('provider', 64)->nullable();
            $table->string('model_version', 80)->nullable();
            $table->string('source_disk', 64)->default('local');
            $table->string('source_path', 500);
            $table->string('source_original_name', 255)->nullable();
            $table->string('source_mime', 120)->nullable();
            $table->unsignedInteger('source_size')->nullable();
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->json('extracted_schema_json')->nullable();
            $table->json('reviewed_schema_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_import_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('form_import_id')->index();
            $table->unsignedInteger('page_number');
            $table->string('path', 500)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
            $table->unique(['form_import_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_import_pages');
        Schema::dropIfExists('form_imports');
    }
};
