<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('feegow_enabled')->default(false)->after('evolution_go_instance_token');
            $table->string('feegow_base_url', 255)->nullable()->after('feegow_enabled');
            $table->text('feegow_token')->nullable()->after('feegow_base_url');
            $table->timestamp('feegow_last_check_at')->nullable()->after('feegow_token');
            $table->string('feegow_last_status', 32)->nullable()->after('feegow_last_check_at');
            $table->text('feegow_last_error')->nullable()->after('feegow_last_status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'feegow_enabled',
                'feegow_base_url',
                'feegow_token',
                'feegow_last_check_at',
                'feegow_last_status',
                'feegow_last_error',
            ]);
        });
    }
};
