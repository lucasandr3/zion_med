<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('address');
            $table->string('contact_email')->nullable()->after('phone');
            $table->string('short_description')->nullable()->after('contact_email');
            $table->string('specialties')->nullable()->after('short_description');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('specialties');
            $table->string('meta_description')->nullable()->after('founded_year');
            $table->string('cover_image_path')->nullable()->after('meta_description');
            $table->string('maps_url')->nullable()->after('cover_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'contact_email',
                'short_description',
                'specialties',
                'founded_year',
                'meta_description',
                'cover_image_path',
                'maps_url',
            ]);
        });
    }
};
