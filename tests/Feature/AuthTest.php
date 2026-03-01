<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ClinicSeeder::class);
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Zion Med');
    }

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = User::withoutGlobalScopes()->where('email', 'admin@demo.zionmed.com')->first();
        $this->assertNotNull($user);

        $response = $this->post(route('login'), [
            'email' => 'admin@demo.zionmed.com',
            'password' => 'senha123',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_credentials_returns_error(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@demo.zionmed.com',
            'password' => 'wrong',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }
}
