<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsappEvolutionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    public function test_whatsapp_evolution_show_requires_auth(): void
    {
        $this->getJson('/api/v1/clinica/whatsapp/evolution')->assertStatus(401);
    }

    public function test_whatsapp_evolution_show_returns_structure_for_owner(): void
    {
        /** @var User $user */
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $response = $this->getJson('/api/v1/clinica/whatsapp/evolution');
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'server_configured',
                'instance_configured',
                'instance_name',
                'has_instance_token',
                'remote_id',
                'connected',
                'logged_in',
                'remote_error',
            ],
        ]);
    }
}
