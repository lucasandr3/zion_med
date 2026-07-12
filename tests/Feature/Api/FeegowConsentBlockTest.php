<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Services\FeegowClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeegowConsentBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    public function test_feegow_create_appointment_blocks_when_consent_pending(): void
    {
        $user = $this->actingOwner();
        $organization = Organization::query()->findOrFail($user->organization_id);
        $organization->update([
            'feegow_enabled' => true,
            'feegow_token' => 'test-token',
            'feegow_base_url' => 'https://api.feegow.com/v1/api',
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Paciente Pendente',
            'code' => 'PEND001',
            'birth_date' => '1990-01-01',
            'status' => 'active',
        ]);

        $consentTemplate = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'TCLE Bloqueio',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'template_id' => $consentTemplate->id,
            'person_id' => $person->id,
            'protocol_number' => 'P-BLOCK-001',
            'submitter_name' => 'Paciente Pendente',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now(),
        ]);

        $this->mock(FeegowClient::class, function ($mock): void {
            $mock->shouldNotReceive('createAppointment');
        });

        $this->postJson('/api/v1/clinica/integracoes/sistemas/feegow/agendamentos', [
            'person_id' => $person->id,
            'local_id' => 1,
            'paciente_id' => 10,
            'profissional_id' => 20,
            'especialidade_id' => 30,
            'procedimento_id' => 40,
            'data' => now()->format('d-m-Y'),
            'horario' => '10:00:00',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'consent_blocks_procedure');
    }

    public function test_validate_person_returns_scheduling_flag_when_feegow_active(): void
    {
        $user = $this->actingOwner();
        $organization = Organization::query()->findOrFail($user->organization_id);
        $organization->update([
            'feegow_enabled' => true,
            'feegow_token' => 'test-token',
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Paciente Gate',
            'code' => 'GATE001',
            'birth_date' => '1985-05-10',
            'status' => 'active',
        ]);

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Ficha com Gate',
            'category' => 'anamnese',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => true,
            'public_person_link_mode' => 'code',
            'public_token' => str_repeat('b', 32),
        ]);

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/validate-person", [
            'code' => $person->code,
            'birth_date' => '1985-05-10',
        ])->assertOk()
            ->assertJsonPath('data.procedure_scheduling_allowed', false)
            ->assertJsonPath('data.consent_summary.status', 'none');
    }

    public function test_public_submit_blocks_feegow_when_consent_not_valid(): void
    {
        $user = $this->actingOwner();
        $organization = Organization::query()->findOrFail($user->organization_id);
        $organization->update([
            'feegow_enabled' => true,
            'feegow_token' => 'test-token',
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Paciente Submit',
            'code' => 'SUB001',
            'birth_date' => '1992-03-15',
            'status' => 'active',
        ]);

        $consentTemplate = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'TCLE Anterior',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'template_id' => $consentTemplate->id,
            'person_id' => $person->id,
            'protocol_number' => 'P-PREV-PEND',
            'submitter_name' => 'Paciente Submit',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now()->subDay(),
        ]);

        $publicTemplate = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => 'Ficha + Feegow',
            'category' => 'anamnese',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => true,
            'public_person_link_mode' => 'code',
            'public_token' => str_repeat('c', 32),
        ]);

        $this->mock(FeegowClient::class, function ($mock): void {
            $mock->shouldNotReceive('createAppointment');
        });

        $this->postJson("/api/v1/formulario-publico/{$publicTemplate->public_token}/submit", [
            '_submitter_name' => 'Paciente Submit',
            '_person_code' => $person->code,
            '_person_birth_date' => '1992-03-15',
            'feegow_paciente_id' => 10,
            'feegow_profissional_id' => 20,
            'feegow_procedimento_id' => 40,
            'feegow_especialidade_id' => 30,
            'feegow_local_id' => 1,
            'feegow_data' => now()->format('Y-m-d'),
            'feegow_horario' => '10:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.feegow.created', false)
            ->assertJsonPath('data.feegow.code', 'consent_blocks_procedure');
    }
}
