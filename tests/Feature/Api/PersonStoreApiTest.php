<?php

namespace Tests\Feature\Api;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonStoreApiTest extends TestCase
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

    public function test_pessoas_store_requires_auth(): void
    {
        $this->postJson('/api/v1/pessoas', ['name' => 'Teste'])->assertStatus(401);
    }

    public function test_pessoas_store_creates_person_for_tenant(): void
    {
        $user = $this->actingOwner();

        $response = $this->postJson('/api/v1/pessoas', [
            'name' => 'Paciente Novo API',
            'email' => 'novo.paciente@test.com',
            'phone' => '5511999887766',
            'cpf' => '52998224725',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Paciente Novo API')
            ->assertJsonPath('data.email', 'novo.paciente@test.com');

        $this->assertDatabaseHas('people', [
            'organization_id' => $user->organization_id,
            'name' => 'Paciente Novo API',
        ]);
    }

    public function test_pessoas_update_scoped_to_tenant(): void
    {
        $user = $this->actingOwner();

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-UPD-001',
            'name' => 'Antes',
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/v1/pessoas/{$person->id}", [
            'name' => 'Depois da API',
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Depois da API');

        $person->refresh();
        $this->assertSame('Depois da API', $person->name);
    }
}
