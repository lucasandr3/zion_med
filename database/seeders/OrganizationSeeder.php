<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    private const string NICHE = 'estetica';

    public function run(): void
    {
        $trialDays = (int) config('asaas.trial_days', 14);
        $trialEndsAt = now()->addDays($trialDays);

        $this->seedQaTenant($trialEndsAt);
        $this->seedDemonstracaoMultiTenant($trialEndsAt);
        $this->seedDemonstracaoSingleTenant($trialEndsAt);
        $this->seedPlatformAdmin();
    }

    private function seedQaTenant(\DateTimeInterface $trialEndsAt): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'qa-gestgo'],
            ['name' => 'QA Gestgo'],
        );

        $organization = Organization::firstOrCreate(
            ['slug' => 'clinica-qa-gestgo'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Clínica QA Gestgo',
                'niche' => self::NICHE,
                'notification_email' => 'qa@gestgo.test',
                'address' => 'Ambiente de testes manuais (QA)',
            ],
        );

        $this->applyTrialBilling($organization, $trialEndsAt);

        $this->seedUser('lucasvieiraandrade58@gmail.com', [
            'organization_id' => $organization->id,
            'name' => 'QA Organização',
            'password' => '12345678',
            'role' => Role::Owner->value,
            'active' => true,
        ]);
    }

    private function seedDemonstracaoMultiTenant(\DateTimeInterface $trialEndsAt): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demonstracao-multi'],
            ['name' => 'Demonstração Multi-clínica'],
        );

        $matriz = Organization::firstOrCreate(
            ['slug' => 'demonstracao-matriz'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demonstração Matriz',
                'niche' => self::NICHE,
                'notification_email' => 'demonstracao@gestgo.test',
                'address' => 'Conta de demonstração para clientes (multi-clínica)',
            ],
        );

        $unidade = Organization::firstOrCreate(
            ['slug' => 'demonstracao-unidade-centro'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demonstração Unidade Centro',
                'niche' => self::NICHE,
                'notification_email' => 'demonstracao@gestgo.test',
                'address' => 'Unidade secundária — demonstração',
            ],
        );

        $this->applyTrialBilling($matriz, $trialEndsAt);
        $this->applyTrialBilling($unidade, $trialEndsAt);

        $this->seedUser('demonstracao@gestgo.test', [
            'organization_id' => $matriz->id,
            'name' => 'Demonstração Multi-clínica',
            'password' => '12345678',
            'role' => Role::Owner->value,
            'active' => true,
            'can_switch_clinic' => true,
        ]);
    }

    private function seedDemonstracaoSingleTenant(\DateTimeInterface $trialEndsAt): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demonstracao-single'],
            ['name' => 'Demonstração Single'],
        );

        $organization = Organization::firstOrCreate(
            ['slug' => 'demonstracao-single'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demonstração Single',
                'niche' => self::NICHE,
                'notification_email' => 'demonstracao-single@gestgo.test',
                'address' => 'Conta de demonstração — uma clínica',
            ],
        );

        $this->applyTrialBilling($organization, $trialEndsAt);

        $this->seedUser('demonstracao-single@gestgo.test', [
            'organization_id' => $organization->id,
            'name' => 'Demonstração Single',
            'password' => '12345678',
            'role' => Role::Owner->value,
            'active' => true,
        ]);
    }

    private function seedPlatformAdmin(): void
    {
        $this->seedUser('admin@gestgo.com.br', [
            'organization_id' => null,
            'name' => 'Admin Plataforma',
            'password' => '12345678',
            'role' => Role::PlatformAdmin->value,
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function seedUser(string $email, array $attributes): User
    {
        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => $email],
            [...$attributes, 'email_verified_at' => now()],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    private function applyTrialBilling(Organization $organization, \DateTimeInterface $trialEndsAt): void
    {
        $organization->update([
            'niche' => self::NICHE,
            'trial_ends_at' => $trialEndsAt,
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);
    }
}
