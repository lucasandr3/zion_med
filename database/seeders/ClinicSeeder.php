<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        // Tenants novos vêm do cadastro (Comece). Aqui só o mínimo para QA: um tenant + uma clínica.
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'clinica-qa-zion'],
            ['name' => 'Tenant QA Zion'],
        );

        $trialDays = (int) config('asaas.trial_days', 14);

        $clinic = Clinic::firstOrCreate(
            ['slug' => 'clinica-qa-zion'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Clínica QA Zion',
                'notification_email' => 'qa@zionmed.test',
                'address' => 'Ambiente de testes manuais (QA)',
            ],
        );

        $clinic->update([
            'trial_ends_at' => now()->addDays($trialDays),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        // Dono da clínica QA (login manual + mesmos testes que usam qaClinicOwnerUser()).
        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'qa-owner@zionmed.test'],
            [
                'organization_id' => $clinic->id,
                'name' => 'QA Clínica',
                'password' => 'senha123',
                'role' => Role::Owner->value,
                'active' => true,
            ]
        );

        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@zionmed.com'],
            [
                'organization_id' => null,
                'name' => 'Admin Plataforma',
                'password' => bcrypt('senha123'),
                'role' => Role::PlatformAdmin->value,
                'active' => true,
            ]
        );
    }
}
