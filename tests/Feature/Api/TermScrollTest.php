<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TermScrollTest extends TestCase
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

    public function test_consentimento_with_notice_requires_term_scrolled_at(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE com termo',
            'description' => null,
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('f', 32),
        ]);

        FormField::withoutGlobalScopes()->create([
            'template_id' => $template->id,
            'organization_id' => $user->organization_id,
            'name_key' => 'termo_tcle',
            'label' => 'Texto longo do termo de consentimento.',
            'type' => 'notice',
            'required' => false,
            'sort_order' => 1,
        ]);

        $token = $template->public_token;
        $scrolledAt = now()->toIso8601String();

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Paciente',
            '_comprehension_ack' => true,
            '_comprehension_ack_at' => now()->toIso8601String(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['_term_scrolled_at']);

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Paciente',
            '_comprehension_ack' => true,
            '_comprehension_ack_at' => now()->toIso8601String(),
            '_term_scrolled_at' => $scrolledAt,
        ])->assertCreated();

        $submission = FormSubmission::withoutGlobalScopes()
            ->where('template_id', $template->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($submission);
        $clinical = $submission->document_snapshot['clinical'] ?? [];
        $this->assertSame($scrolledAt, $clinical['term_scrolled_at'] ?? null);
    }

    public function test_consentimento_without_notice_does_not_require_term_scrolled_at(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE sem notice',
            'description' => null,
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('g', 32),
        ]);

        $token = $template->public_token;

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Paciente',
            '_comprehension_ack' => true,
            '_comprehension_ack_at' => now()->toIso8601String(),
        ])->assertCreated();
    }
}
