<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sends', function (Blueprint $table) {
            $table->string('send_token', 64)->nullable()->unique()->after('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('document_sends', function (Blueprint $table) {
            $table->dropUnique(['send_token']);
            $table->dropColumn('send_token');
        });
    }
};
