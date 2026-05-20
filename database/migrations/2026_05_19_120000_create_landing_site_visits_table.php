<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64);
            $table->date('visit_date');
            $table->string('path', 500)->default('/');
            $table->timestamps();

            // Um registro por IP por dia (primeiro path acessado no dia fica salvo).
            $table->unique(['ip_hash', 'visit_date']);
            $table->index(['visit_date', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_site_visits');
    }
};
