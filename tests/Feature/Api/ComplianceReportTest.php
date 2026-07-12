<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceReportTest extends TestCase
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

    public function test_compliance_report_returns_consent_metrics(): void
    {
        $user = $this->actingOwner();

        $consentTemplate = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Compliance',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $consentTemplate->id,
            'protocol_number' => 'P-COMP-PEND',
            'submitter_name' => 'Paciente Pendente',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now()->subDays(2),
            'document_snapshot' => ['fields' => []],
            'document_snapshot_hash' => 'abc123',
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $consentTemplate->id,
            'protocol_number' => 'P-COMP-EXP',
            'submitter_name' => 'Paciente Vencido',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now()->subMonth(),
            'approved_at' => now()->subMonth(),
            'consent_valid_until' => now()->subDay(),
            'document_snapshot' => ['fields' => []],
            'document_snapshot_hash' => 'def456',
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $consentTemplate->id,
            'protocol_number' => 'P-COMP-NOSNAP',
            'submitter_name' => 'Sem Snapshot',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now()->subDays(3),
            'approved_at' => now()->subDays(3),
            'document_snapshot' => null,
            'document_snapshot_hash' => null,
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $consentTemplate->id,
            'protocol_number' => 'P-COMP-REV',
            'submitter_name' => 'Revogado',
            'status' => SubmissionStatus::Revoked,
            'submitted_at' => now()->subWeek(),
            'revoked_at' => now()->subDay(),
            'document_snapshot' => ['fields' => []],
            'document_snapshot_hash' => 'ghi789',
        ]);

        $this->getJson('/api/v1/compliance/relatorio?scope=consent')
            ->assertOk()
            ->assertJsonPath('data.summary.pending', 1)
            ->assertJsonPath('data.summary.consent_expired', 1)
            ->assertJsonPath('data.summary.without_snapshot', 1)
            ->assertJsonPath('data.summary.revoked', 1)
            ->assertJsonPath('data.scope', 'consent')
            ->assertJson(fn ($json) => $json->has('data.recent_issues')->etc());
    }
}
