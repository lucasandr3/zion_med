<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_users')->nullable()->after('description');
            $table->unsignedSmallInteger('max_organizations_per_tenant')->nullable()->after('max_users');
        });

        Plan::query()->update([
            'max_organizations_per_tenant' => 1,
        ]);

        Plan::query()->where('key', 'executive')->update([
            'name' => 'Gestgo Business',
            'value' => 247.00,
            'description' => 'Para clínicas e equipes: mesmo núcleo do Profissional, com usuários ilimitados no plano.',
            'max_users' => null,
            'max_organizations_per_tenant' => 1,
            'sort_order' => 10,
        ]);

        Plan::updateOrCreate(
            ['key' => 'solo'],
            [
                'name' => 'Gestgo Profissional',
                'value' => 97.00,
                'description' => 'Para autônomos: fichas digitais, consentimentos, assinatura eletrônica, protocolo, PDF, link da bio e templates prontos — até 2 usuários.',
                'sort_order' => 0,
                'is_active' => true,
                'max_users' => 2,
                'max_organizations_per_tenant' => 1,
            ]
        );

        if (Plan::query()->where('key', 'executive')->doesntExist()) {
            Plan::create([
                'key' => 'executive',
                'name' => 'Gestgo Business',
                'value' => 247.00,
                'description' => 'Para clínicas e equipes: tudo do plano Profissional, com limites ampliados de usuários.',
                'sort_order' => 10,
                'is_active' => true,
                'max_users' => null,
                'max_organizations_per_tenant' => 1,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'max_organizations_per_tenant']);
        });
    }
};
