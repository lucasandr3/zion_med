<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('fields_snapshot'); // snapshot of fields at publish time
            $table->timestamps();
        });

        Schema::table('form_template_versions', function (Blueprint $table) {
            $table->unique(['form_template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_template_versions');
    }
};
