<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_import_pages', function (Blueprint $table): void {
            $table->string('original_name', 255)->nullable()->after('path');
            $table->string('mime', 120)->nullable()->after('original_name');
            $table->unsignedInteger('size')->nullable()->after('mime');
        });
    }

    public function down(): void
    {
        Schema::table('form_import_pages', function (Blueprint $table): void {
            $table->dropColumn(['original_name', 'mime', 'size']);
        });
    }
};
