<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('google_place_id', 255)->nullable()->after('maps_url');
            $table->boolean('google_reviews_enabled')->default(false)->after('google_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['google_place_id', 'google_reviews_enabled']);
        });
    }
};
