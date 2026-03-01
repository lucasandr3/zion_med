<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_bio_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_link_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['clinic_link_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_bio_link_clicks');
    }
};
