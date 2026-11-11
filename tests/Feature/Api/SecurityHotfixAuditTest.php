<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\Billing\BillingStateService;
use App\Services\SubmissionService;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regressão dos hotfixes de segurança críticos (sem impacto em clientes active/trial).
 */
final class SecurityHotfixAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationSeeder::class);
    }

    public function test_owner_cannot_create_platform_admin_role_or_escalate(): void
    {
        $owner = $this->qaClinicOwnerUser();
        Sanctum::actingAs($owner);
        session([
            'current_organization_id' => $owner->organization_id,
            'current_clinic_id' => $owner->organization_id,
        ]);

        $this->postJson('/api/v1/organization-roles', [
            'slug' => 'platform_admin',
            'label' => 'Fake Platform',
            'permissions' => ['submissions.view'],
        ])->assertStatus(422);

        // Mesmo se o role existir no banco, atribuição e isPlatformAdmin ficam bloqueados.
        \App\Models\OrganizationRole::query()->create([
            'organization_id' => $owner->organization_id,
            'slug' => 'platform_admin',
            'label' => 'Fake',
            'is_system' => false,
            'is_assignable' => true,
            'permissions' => ['submissions.view'],
        ]);

        $this->putJson('/api/v1/usuarios/'.$owner->id, [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => 'platform_admin',
            'active' => true,
        ])->assertStatus(422);

        $owner->forceFill(['role' => Role::PlatformAdmin->value])->save();
        $owner->refresh();
        $this->assertFalse($owner->isPlatformAdmin());
        $this->assertNotSame(
            \App\Support\Permission::keys(),
            $owner->effectivePermissions(),
            'Usuário de clínica com role platform_admin não deve receber todas as permissões.'
        );

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/platform/plans')->assertForbidden();
    }

    public function test_real_platform_admin_still_works(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('role', Role::PlatformAdmin->value)
            ->whereNull('organization_id')
            ->firstOrFail();

        $this->assertTrue($admin->isPlatformAdmin());
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/platform/plans')->assertOk();
    }

    public function test_notifications_do_not_leak_to_other_org_super_admin(): void
    {
        $orgA = Organization::query()->firstOrFail();
        $orgB = Organization::query()->create([
            'tenant_id' => $orgA->tenant_id,
            'name' => 'Outra Clínica Leak Test',
            'slug' => 'outra-clinica-leak-test',
            'niche' => 'estetica',
            'notification_email' => 'leak-b@gestgo.test',
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $superB = User::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'name' => 'Super B',
            'email' => 'super-b-leak@gestgo.test',
            'password' => Hash::make('senha123'),
            'role' => Role::SuperAdmin->value,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $service = app(SubmissionService::class);
        $method = new ReflectionMethod(SubmissionService::class, 'getClinicUsers');
        $method->setAccessible(true);
        /** @var \Illuminate\Support\Collection<int, User> $recipients */
        $recipients = $method->invoke($service, (int) $orgA->id);

        $this->assertFalse($recipients->contains(fn (User $u) => (int) $u->id === (int) $superB->id));
        $this->assertTrue($recipients->every(fn (User $u) => (int) $u->organization_id === (int) $orgA->id));
    }

    public function test_canceled_subscription_does_not_keep_access_via_old_payment(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;
        $org->update([
            'trial_ends_at' => now()->subMonths(3),
            'subscription_status' => 'canceled',
            'billing_status' => 'canceled',
        ]);
        Payment::create([
            'organization_id' => $org->id,
            'asaas_payment_id' => 'pay_hotfix_old_'.uniqid(),
            'status' => 'RECEIVED',
            'due_date' => now()->subMonths(2),
            'paid_at' => now()->subMonths(2),
            'value' => 97,
        ]);

        $billing = app(BillingStateService::class);
        $this->assertFalse($billing->canAccessTenantAppFeatures($org->fresh()));
    }

    public function test_active_paid_customer_keeps_access(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;
        $org->update([
            'trial_ends_at' => now()->subDays(30),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
        ]);
        Payment::create([
            'organization_id' => $org->id,
            'asaas_payment_id' => 'pay_hotfix_active_'.uniqid(),
            'status' => 'RECEIVED',
            'due_date' => now()->subDays(5),
            'paid_at' => now()->subDays(5),
            'value' => 97,
        ]);

        $billing = app(BillingStateService::class);
        $this->assertTrue($billing->canAccessTenantAppFeatures($org->fresh()));
    }

    public function test_trial_customer_keeps_access(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;
        $org->update([
            'trial_ends_at' => now()->addDays(10),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        $billing = app(BillingStateService::class);
        $this->assertTrue($billing->canAccessTenantAppFeatures($org->fresh()));
    }
}
