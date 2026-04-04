<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UserPlanLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cannot_create_user_when_solo_plan_user_limit_reached(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;
        $org->update([
            'plan_key' => 'solo',
            'trial_ends_at' => now()->addDays(10),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        User::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Segundo usuário',
            'email' => 'segundo@gestgo.test',
            'password' => Hash::make('senha123'),
            'role' => Role::Staff->value,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/usuarios', [
            'name' => 'Terceiro',
            'email' => 'terceiro@gestgo.test',
            'password' => 'SenhaSegura1!',
            'password_confirmation' => 'SenhaSegura1!',
            'role' => 'staff',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'O plano atual atingiu o limite de usuários. Faça upgrade do plano ou desative outro usuário antes de adicionar um novo.',
        ]);
    }
}
