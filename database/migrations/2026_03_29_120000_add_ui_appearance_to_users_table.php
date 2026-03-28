<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ui_theme', 64)->nullable()->after('can_switch_clinic');
            $table->boolean('ui_dark_mode')->nullable()->after('ui_theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ui_theme', 'ui_dark_mode']);
        });
    }
};
