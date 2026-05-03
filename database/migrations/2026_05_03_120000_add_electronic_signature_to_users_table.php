<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('electronic_signature_path')->nullable()->after('ui_dark_mode');
            $table->timestamp('electronic_signature_updated_at')->nullable()->after('electronic_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['electronic_signature_path', 'electronic_signature_updated_at']);
        });
    }
};
