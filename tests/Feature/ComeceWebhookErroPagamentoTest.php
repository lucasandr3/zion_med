<?php

namespace Tests\Feature;

use App\Models\User;
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Clínica Teste',
            'responsible_name' => 'Responsável Teste',
            'email' => 'novo@teste.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'plan_key' => 'core',
            'accepted_terms' => '1',
        ], $overrides);
    }

    public function test_validation_error_sends_webhook(): void
    {
        Http::fake();

        $response = $this->post(route('comece.store'), $this->validPayload([
            'company_name' => '', // inválido
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('company_name');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }
            $body = $request->data();
            return $body['source'] === 'comece_landing'
                && $body['error_type'] === 'validation'
                && isset($body['message'])
                && isset($body['data']['email'])
                && isset($body['data']['plan_key'])
                && isset($body['occurred_at']);
        });
    }

    public function test_validation_error_payload_does_not_contain_password(): void
    {
        Http::fake();

        $this->post(route('comece.store'), $this->validPayload([
            'responsible_name' => '',
        ]));

        Http::assertSent(function ($request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }
            $data = $request->data();
            return ! array_key_exists('password', $data['data'] ?? [])
                && ! array_key_exists('password_confirmation', $data['data'] ?? []);
        });
    }

    public function test_payment_error_sends_webhook_and_user_still_created(): void
    {
        Http::fake();

        $this->mock(AsaasService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createSubscription')
                ->andThrow(new \RuntimeException('Asaas API error'));
        });

        $payload = $this->validPayload(['email' => 'pagamento-erro@teste.com']);

        $response = $this->post(route('comece.store'), $payload);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'pagamento-erro@teste.com']);

        Http::assertSent(function ($request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }
            $body = $request->data();
            return $body['source'] === 'comece_landing'
                && $body['error_type'] === 'payment'
                && $body['message'] === 'Asaas API error'
                && ($body['data']['company_name'] ?? '') === 'Clínica Teste'
                && ($body['data']['plan_key'] ?? '') === 'core';
        });
    }

    public function test_successful_registration_without_asaas_does_not_send_webhook(): void
    {
        Http::fake();

        $this->mock(AsaasService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        $response = $this->post(route('comece.store'), $this->validPayload([
            'email' => 'sucesso@teste.com',
        ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        Http::assertNotSent(function ($request): bool {
            return str_contains($request->url(), 'erro-pagamento');
        });
    }

    public function test_webhook_not_called_when_url_not_configured(): void
    {
        config(['services.n8n_webhook_erro_pagamento' => '']);
        Http::fake();

        $this->post(route('comece.store'), $this->validPayload([
            'company_name' => '',
        ]));

        Http::assertNothingSent();
    }
}
