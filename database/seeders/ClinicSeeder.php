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
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'clinica-demo-zion'],
            ['name' => 'Clínica Demo Zion'],
        );

        $clinics = [
            [
                'name' => 'Clínica Demo Zion - Matriz',
                'slug' => 'clinica-demo-zion-matriz',
                'notification_email' => 'recepcao@demo.zionmed.com',
                'address' => 'Rua Central, 45 – Bela Vista',
            ],
            [
                'name' => 'Clínica Demo Zion - Filial Centro',
                'slug' => 'clinica-demo-zion-centro',
                'notification_email' => 'centro@demo.zionmed.com',
                'address' => 'Av. Paulista, 1000 – Centro',
            ],
            [
                'name' => 'Clínica Demo Zion - Filial Sul',
                'slug' => 'clinica-demo-zion-sul',
                'notification_email' => 'sul@demo.zionmed.com',
                'address' => 'Rua das Flores, 220 – Vila Sul',
            ],
        ];

        $trialDays = (int) config('asaas.trial_days', 14);

        foreach ($clinics as $index => $data) {
            $clinic = Clinic::create(array_merge($data, ['tenant_id' => $tenant->id]));

            if ($index === 0) {
                $clinic->update([
                    'trial_ends_at' => now()->addDays($trialDays),
                    'subscription_status' => 'trial',
                    'billing_status' => 'ok',
                ]);
            }

            $emails = [
                'admin@demo.zionmed.com',      // Matriz
                'admin-centro@demo.zionmed.com',
                'admin-sul@demo.zionmed.com',
            ];

            User::create([
                'organization_id' => $clinic->id,
                'name' => 'Admin Demo',
                'email' => $emails[$index],
                'password' => bcrypt('senha123'),
                'role' => Role::Owner,
                'active' => true,
            ]);
        }

        // Usuário PlatformAdmin: dono da plataforma, não está vinculado a tenant/clinic.
        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@zionmed.com'],
            [
                'organization_id' => null,
                'name' => 'Admin Plataforma',
                'password' => bcrypt('senha123'),
                'role' => Role::PlatformAdmin,
                'active' => true,
            ]
        );
    }
}
