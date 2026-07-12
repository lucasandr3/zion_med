<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProtocolRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    public function test_preview_counts_eligible_protocols(): void
    {
        $user = $this->actingOwner();
        $org = Organization::withoutGlobalScopes()->findOrFail($user->organization_id);
        $org->update([
            'protocol_retention_years' => 5,
            'protocol_retention_mode' => 'anonymize',
        ]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->firstOrFail();

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'template_id' => $template->id,
            'protocol_number' => 'P-OLD-001',
            'submitter_name' => 'Paciente Antigo',
            'submitter_email' => 'old@test.com',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now()->subYears(6),
            'approved_at' => now()->subYears(6),
        ]);

        $this->getJson('/api/v1/clinica/retencao-protocolos/preview')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.eligible_count', 1);
    }

    public function test_apply_retention_anonymizes_old_protocol(): void
    {
        $user = $this->actingOwner();
        $org = Organization::withoutGlobalScopes()->findOrFail($user->organization_id);
        $org->update(['protocol_retention_years' => 1, 'protocol_retention_mode' => 'anonymize']);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->firstOrFail();

        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'template_id' => $template->id,
            'protocol_number' => 'P-RET-001',
            'submitter_name' => 'Nome Sensível',
            'submitter_email' => 'sensivel@test.com',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now()->subYears(2),
            'approved_at' => now()->subYears(2),
            'document_snapshot' => ['values' => ['nome' => 'João']],
        ]);

        $this->artisan('protocols:apply-retention')->assertSuccessful();

        $fresh = $submission->fresh();
        $this->assertNotNull($fresh->retention_anonymized_at);
        $this->assertSame('[dados retidos por política LGPD]', $fresh->submitter_name);
        $this->assertNull($fresh->submitter_email);
    }
}
