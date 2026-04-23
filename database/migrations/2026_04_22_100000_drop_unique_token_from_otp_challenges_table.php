<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->dropUnique(['token']);
        });
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->index(['token', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->dropIndex(['token', 'recipient']);
        });
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->unique('token');
        });
    }
};
