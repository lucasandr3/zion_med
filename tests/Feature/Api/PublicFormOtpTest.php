<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicFormOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_otp_send_returns_201_with_valid_token(): void
    {
        $template = FormTemplate::withoutGlobalScopes()->first();
        $token = str_repeat('a', 32);
        $template->update(['public_enabled' => true, 'public_token' => $token]);

        $response = $this->postJson("/api/v1/formulario-publico/{$token}/otp/send", [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.message', 'Código enviado por e-mail.');
        $response->assertJsonPath('data.expires_in_minutes', 10);
        $this->assertDatabaseHas('otp_challenges', [
            'token' => $token,
            'recipient' => 'user@example.com',
            'channel' => 'email',
        ]);
    }

    public function test_otp_send_returns_404_for_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/formulario-publico/invalid-token-123/otp/send', [
            'email' => 'user@example.com',
        ]);
        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Formulário não encontrado.');
    }

    public function test_otp_verify_success_returns_200(): void
    {
        $template = FormTemplate::withoutGlobalScopes()->first();
        $token = str_repeat('b', 32);
        $template->update(['public_enabled' => true, 'public_token' => $token]);

        OtpChallenge::create([
            'token' => $token,
            'channel' => 'email',
            'recipient' => 'verify@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson("/api/v1/formulario-publico/{$token}/otp/verify", [
            'email' => 'verify@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.message', 'Código verificado com sucesso.');
    }

    public function test_otp_verify_invalid_code_returns_422(): void
    {
        $template = FormTemplate::withoutGlobalScopes()->first();
        $token = str_repeat('c', 32);
        $template->update(['public_enabled' => true, 'public_token' => $token]);

        OtpChallenge::create([
            'token' => $token,
            'channel' => 'email',
            'recipient' => 'bad@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson("/api/v1/formulario-publico/{$token}/otp/verify", [
            'email' => 'bad@example.com',
            'code' => '000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Código inválido ou expirado.');
    }
}
