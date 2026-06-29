<?php

namespace Tests\Feature\Api;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonPiiApiTest extends TestCase
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

    public function test_pessoas_index_masks_pii(): void
    {
        $user = $this->actingOwner();

        Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-000099',
            'name' => 'Paciente Teste PII',
            'email' => 'paciente.pii@test.com',
            'cpf' => '52998224725',
            'phone' => '11987654321',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/pessoas?search=Paciente+Teste+PII');

        $response->assertOk();
        $email = $response->json('data.0.email');
        $cpf = $response->json('data.0.cpf');
        $phone = $response->json('data.0.phone');

        $this->assertStringContainsString('***', (string) $email);
        $this->assertStringContainsString('***', (string) $cpf);
        $this->assertStringContainsString('*****', (string) $phone);
    }

    public function test_pessoas_show_exposes_pii_for_editing(): void
    {
        $user = $this->actingOwner();

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-000100',
            'name' => 'Paciente Edição',
            'email' => 'edicao.pii@test.com',
            'cpf' => '52998224725',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/pessoas/{$person->id}");

        $response->assertOk();
        $response->assertJsonPath('data.email', 'edicao.pii@test.com');
        $response->assertJsonPath('data.cpf', '52998224725');
    }
}
