<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($clinics as $index => $data) {
            $clinic = Clinic::create($data);

            $emails = [
                'admin@demo.zionmed.com',      // Matriz
                'admin-centro@demo.zionmed.com',
                'admin-sul@demo.zionmed.com',
            ];

            User::create([
                'clinic_id' => $clinic->id,
                'name' => 'Admin Demo',
                'email' => $emails[$index],
                'password' => bcrypt('senha123'),
                'role' => Role::Owner,
                'active' => true,
            ]);
        }

        // SuperAdmin: acessa todas as clínicas (clinic_id null), senha: senha123
        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'super@demo.zionmed.com'],
            [
                'clinic_id' => null,
                'name' => 'Super Admin',
                'password' => bcrypt('senha123'),
                'role' => Role::SuperAdmin,
                'active' => true,
            ]
        );
    }
}
