<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained('clinics')->nullOnDelete();
            $table->string('role', 20)->default('staff')->after('password');
            $table->boolean('active')->default(true)->after('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['clinic_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['clinic_id', 'email']);
            $table->unique(['email']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropColumn(['clinic_id', 'role', 'active']);
        });
    }
};
