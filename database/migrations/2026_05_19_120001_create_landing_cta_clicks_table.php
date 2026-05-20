<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 80);
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['channel', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_cta_clicks');
    }
};
