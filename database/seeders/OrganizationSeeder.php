<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'clinica-qa-gestgo'],
            ['name' => 'Tenant QA Gestgo'],
        );

        $trialDays = (int) config('asaas.trial_days', 14);

        $organization = Organization::firstOrCreate(
            ['slug' => 'clinica-qa-gestgo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Organização QA Gestgo',
                'notification_email' => 'qa@gestgo.test',
                'address' => 'Ambiente de testes manuais (QA)',
            ],
        );

        $organization->update([
            'trial_ends_at' => now()->addDays($trialDays),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'qa-owner@gestgo.test'],
            [
                'organization_id' => $organization->id,
                'name' => 'QA Organização',
                'password' => 'senha123',
                'role' => Role::Owner->value,
                'active' => true,
            ]
        );

        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@gestgo.com'],
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
