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

            // Um registro por IP por dia por página (path).
            $table->unique(['ip_hash', 'visit_date', 'path'], 'landing_site_visits_ip_date_path_unique');
            $table->index(['visit_date', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_site_visits');
    }
};
