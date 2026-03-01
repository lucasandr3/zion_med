<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('type', 30); // text, textarea, number, date, select, checkbox, radio, file, signature
            $table->string('label');
            $table->string('name_key');
            $table->boolean('required')->default(false);
            $table->json('options_json')->nullable(); // for select/radio options
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
