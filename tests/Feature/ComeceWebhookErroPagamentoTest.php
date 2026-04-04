<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AsaasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ComeceWebhookErroPagamentoTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = 'https://n8n-webhook.gestgo.com.br/webhook/erro-pagamento';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.n8n_webhook_erro_pagamento' => self::WEBHOOK_URL]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Clínica Teste',
            'responsible_name' => 'Responsável Teste',
            'email' => 'novo@teste.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'plan_key' => 'solo',
            'accepted_terms' => true,
        ], $overrides);
    }

    public function test_validation_error_returns_422_and_does_not_send_webhook(): void
    {
        Http::fake();

        $response = $this->postJson(route('api.v1.comece.store'), $this->validPayload([
            'company_name' => '',
        ]));

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_payment_error_sends_webhook_without_password_and_user_still_created(): void
    {
        Http::fake();

        $this->mock(AsaasService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createSubscription')
                ->andThrow(new \RuntimeException('Asaas API error'));
        });

        $payload = $this->validPayload([
            'email' => 'pagamento-erro@teste.com',
            'billing_document' => '12345678901',
        ]);

        $response = $this->postJson(route('api.v1.comece.store'), $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'pagamento-erro@teste.com']);

        Http::assertSent(function ($request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }
            $body = $request->data();
            $data = $body['data'] ?? [];

            return $body['source'] === 'comece_landing'
                && $body['error_type'] === 'payment'
                && $body['message'] === 'Asaas API error'
                && ($data['company_name'] ?? '') === 'Clínica Teste'
                && ($data['plan_key'] ?? '') === 'solo'
                && ! array_key_exists('password', $data)
                && ! array_key_exists('password_confirmation', $data);
        });
    }

    public function test_successful_registration_without_asaas_does_not_send_webhook(): void
    {
        Http::fake();

        $this->mock(AsaasService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        $response = $this->postJson(route('api.v1.comece.store'), $this->validPayload([
            'email' => 'sucesso@teste.com',
        ]));

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['token', 'user', 'organizations'],
        ]);

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'erro-pagamento'));
    }

    public function test_webhook_not_called_when_url_not_configured(): void
    {
        config(['services.n8n_webhook_erro_pagamento' => '']);
        Http::fake();

        $this->mock(AsaasService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createSubscription')
                ->andThrow(new \RuntimeException('Asaas API error'));
        });

        $this->postJson(route('api.v1.comece.store'), $this->validPayload([
            'email' => 'no-url@teste.com',
            'billing_document' => '12345678901',
        ]));

        Http::assertNothingSent();
    }
}
