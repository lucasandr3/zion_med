<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_site_visits')) {
            return;
        }

        Schema::table('landing_site_visits', function (Blueprint $table) {
            try {
                $table->dropUnique(['ip_hash', 'visit_date']);
            } catch (\Throwable) {
                // Já migrado ou índice com outro nome.
            }
            try {
                $table->unique(['ip_hash', 'visit_date', 'path'], 'landing_site_visits_ip_date_path_unique');
            } catch (\Throwable) {
                // Índice novo já existe.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_site_visits')) {
            return;
        }

        Schema::table('landing_site_visits', function (Blueprint $table) {
            try {
                $table->dropUnique('landing_site_visits_ip_date_path_unique');
            } catch (\Throwable) {
            }
            try {
                $table->unique(['ip_hash', 'visit_date']);
            } catch (\Throwable) {
            }
        });
    }
};
