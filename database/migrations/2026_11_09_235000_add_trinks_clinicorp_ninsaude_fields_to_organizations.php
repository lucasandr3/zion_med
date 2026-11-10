<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            // Trinks
            $table->boolean('trinks_enabled')->default(false)->after('feegow_last_error');
            $table->string('trinks_base_url', 255)->nullable()->after('trinks_enabled');
            $table->text('trinks_token')->nullable()->after('trinks_base_url');
            $table->string('trinks_estabelecimento_id', 64)->nullable()->after('trinks_token');
            $table->timestamp('trinks_last_check_at')->nullable()->after('trinks_estabelecimento_id');
            $table->string('trinks_last_status', 32)->nullable()->after('trinks_last_check_at');
            $table->text('trinks_last_error')->nullable()->after('trinks_last_status');

            // Clinicorp
            $table->boolean('clinicorp_enabled')->default(false)->after('trinks_last_error');
            $table->string('clinicorp_base_url', 255)->nullable()->after('clinicorp_enabled');
            $table->string('clinicorp_api_user', 191)->nullable()->after('clinicorp_base_url');
            $table->text('clinicorp_token')->nullable()->after('clinicorp_api_user');
            $table->string('clinicorp_subscriber_id', 64)->nullable()->after('clinicorp_token');
            $table->timestamp('clinicorp_last_check_at')->nullable()->after('clinicorp_subscriber_id');
            $table->string('clinicorp_last_status', 32)->nullable()->after('clinicorp_last_check_at');
            $table->text('clinicorp_last_error')->nullable()->after('clinicorp_last_status');

            // Ninsaúde
            $table->boolean('ninsaude_enabled')->default(false)->after('clinicorp_last_error');
            $table->string('ninsaude_base_url', 255)->nullable()->after('ninsaude_enabled');
            $table->string('ninsaude_account', 191)->nullable()->after('ninsaude_base_url');
            $table->string('ninsaude_username', 191)->nullable()->after('ninsaude_account');
            $table->text('ninsaude_password')->nullable()->after('ninsaude_username');
            $table->text('ninsaude_refresh_token')->nullable()->after('ninsaude_password');
            $table->timestamp('ninsaude_last_check_at')->nullable()->after('ninsaude_refresh_token');
            $table->string('ninsaude_last_status', 32)->nullable()->after('ninsaude_last_check_at');
            $table->text('ninsaude_last_error')->nullable()->after('ninsaude_last_status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'trinks_enabled',
                'trinks_base_url',
                'trinks_token',
                'trinks_estabelecimento_id',
                'trinks_last_check_at',
                'trinks_last_status',
                'trinks_last_error',
                'clinicorp_enabled',
                'clinicorp_base_url',
                'clinicorp_api_user',
                'clinicorp_token',
                'clinicorp_subscriber_id',
                'clinicorp_last_check_at',
                'clinicorp_last_status',
                'clinicorp_last_error',
                'ninsaude_enabled',
                'ninsaude_base_url',
                'ninsaude_account',
                'ninsaude_username',
                'ninsaude_password',
                'ninsaude_refresh_token',
                'ninsaude_last_check_at',
                'ninsaude_last_status',
                'ninsaude_last_error',
            ]);
        });
    }
};
