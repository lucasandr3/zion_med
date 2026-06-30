<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\DocumentSend;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteBindingIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    private function activateOrganization(Clinic $org): void
    {
        $org->forceFill([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'billing_status' => 'ok',
            'plan_key' => 'solo',
        ])->save();
    }

    private function actingAsTenant(User $user, int $organizationId): static
    {
        Sanctum::actingAs($user->fresh());
        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        return $this;
    }

    public function test_cannot_access_other_tenant_protocol_via_route_binding(): void
    {
        $this->seed(\Database\Seeders\OrganizationSeeder::class);

        $userA = $this->qaClinicOwnerUser();
        $userA->forceFill(['email_verified_at' => now()])->save();
        $orgA = Clinic::findOrFail($userA->organization_id);
        $this->activateOrganization($orgA);

        $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $orgB = Clinic::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Org B',
            'slug' => 'org-b',
            'notification_email' => 'b@test.com',
        ]);
        $this->activateOrganization($orgB);

        $templateB = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'name' => 'Template B',
            'is_active' => true,
            'public_enabled' => false,
        ]);

        $protocolB = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'template_id' => $templateB->id,
            'status' => 'pending',
            'protocol_number' => 'ZM-SECRET-B',
            'submitter_name' => 'Paciente B',
            'submitter_email' => 'b@secret.com',
        ]);

        $this->actingAsTenant($userA, $orgA->id)
            ->getJson('/api/v1/protocols/'.$protocolB->id)
            ->assertNotFound();
    }

    public function test_cannot_access_other_tenant_document_send_via_route_binding(): void
    {
        $this->seed(\Database\Seeders\OrganizationSeeder::class);

        $userA = $this->qaClinicOwnerUser();
        $userA->forceFill(['email_verified_at' => now()])->save();
        $orgA = Clinic::findOrFail($userA->organization_id);
        $this->activateOrganization($orgA);

        $tenantB = Tenant::create(['name' => 'Tenant B Doc', 'slug' => 'tenant-b-doc']);
        $orgB = Clinic::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Org B Doc',
            'slug' => 'org-b-doc',
            'notification_email' => 'b-doc@test.com',
        ]);
        $this->activateOrganization($orgB);

        $templateB = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'name' => 'Template B Doc',
            'is_active' => true,
            'public_enabled' => false,
        ]);

        $sendB = DocumentSend::create([
            'organization_id' => $orgB->id,
            'form_template_id' => $templateB->id,
            'recipient_email' => 'secret@other.com',
            'channel' => 'email',
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAsTenant($userA, $orgA->id)
            ->postJson('/api/v1/document-sends/'.$sendB->id.'/cancel')
            ->assertNotFound();
    }

    public function test_can_access_own_tenant_protocol_via_route_binding(): void
    {
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);

        $user = $this->qaClinicOwnerUser();
        $user->forceFill(['email_verified_at' => now()])->save();
        $orgId = (int) $user->organization_id;

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $protocol = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $orgId,
            'template_id' => $template->id,
            'status' => 'pending',
            'protocol_number' => 'ZM-OWN-001',
            'submitter_name' => 'Paciente A',
            'submitter_email' => 'a@own.com',
        ]);

        $this->actingAsTenant($user, $orgId)
            ->getJson('/api/v1/protocols/'.$protocol->id)
            ->assertOk()
            ->assertJsonPath('data.protocol_number', 'ZM-OWN-001');
    }
}
