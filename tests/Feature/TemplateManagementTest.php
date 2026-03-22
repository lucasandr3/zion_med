<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_index_requires_authentication(): void
    {
        $response = $this->get(route('templates.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_create_template(): void
    {
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $user = $this->qaClinicOwnerUser();
        $this->actingAs($user);
        session(['current_clinic_id' => $user->clinic_id]);

        $response = $this->post(route('templates.store'), [
            '_token' => csrf_token(),
            'name' => 'Novo Template',
            'description' => 'Desc',
            'is_active' => '1',
            'public_enabled' => '0',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('form_templates', ['name' => 'Novo Template']);
    }
}
