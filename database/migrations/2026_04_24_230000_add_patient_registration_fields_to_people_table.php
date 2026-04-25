<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('phone_alt', 50)->nullable()->after('phone');
            $table->unsignedTinyInteger('age')->nullable()->after('birth_date');
            $table->string('sex', 1)->nullable()->after('age');
            $table->string('rg', 30)->nullable()->after('cpf');
            $table->string('marital_status', 50)->nullable()->after('rg');
            $table->string('profession')->nullable()->after('marital_status');
            $table->string('referred_by')->nullable()->after('profession');
            $table->string('address')->nullable()->after('referred_by');
            $table->string('neighborhood')->nullable()->after('address');
            $table->string('city')->nullable()->after('neighborhood');
            $table->string('cep', 20)->nullable()->after('city');

            $table->boolean('lead_source_instagram')->default(false)->after('cep');
            $table->boolean('lead_source_google')->default(false)->after('lead_source_instagram');
            $table->boolean('lead_source_facebook')->default(false)->after('lead_source_google');
            $table->boolean('lead_source_indicacao_amigo')->default(false)->after('lead_source_facebook');
            $table->boolean('lead_source_indicacao_medica')->default(false)->after('lead_source_indicacao_amigo');
            $table->boolean('lead_source_plano_saude')->default(false)->after('lead_source_indicacao_medica');
            $table->string('lead_source_outro')->nullable()->after('lead_source_plano_saude');

            $table->string('has_health_plan', 3)->nullable()->after('lead_source_outro');
            $table->string('health_plan_operator')->nullable()->after('has_health_plan');
            $table->string('health_plan_card_number', 100)->nullable()->after('health_plan_operator');
            $table->boolean('lgpd_accept_comms')->default(false)->after('health_plan_card_number');
            $table->boolean('lgpd_accept_reminders')->default(false)->after('lgpd_accept_comms');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'phone_alt',
                'age',
                'sex',
                'rg',
                'marital_status',
                'profession',
                'referred_by',
                'address',
                'neighborhood',
                'city',
                'cep',
                'lead_source_instagram',
                'lead_source_google',
                'lead_source_facebook',
                'lead_source_indicacao_amigo',
                'lead_source_indicacao_medica',
                'lead_source_plano_saude',
                'lead_source_outro',
                'has_health_plan',
                'health_plan_operator',
                'health_plan_card_number',
                'lgpd_accept_comms',
                'lgpd_accept_reminders',
            ]);
        });
    }
};
