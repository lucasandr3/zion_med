<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\PlatformSetting;
use App\Services\BusinessHubConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class BusinessHubConnectorTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-connector-token';

    protected function setUp(): void
    {
        parent::setUp();
        PlatformSetting::set(BusinessHubConfigService::KEY_TOKEN, Crypt::encryptString(self::TOKEN));
        PlatformSetting::set(BusinessHubConfigService::KEY_ENABLED, '1');
        PlatformSetting::set(BusinessHubConfigService::KEY_TYPE, BusinessHubConfigService::DEFAULT_TYPE);
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    public function test_health_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/conector/health', [
            'X-Tenant-Id' => 't1',
        ])->assertStatus(401);
    }

    public function test_health_requires_tenant_header(): void
    {
        $this->getJson('/api/v1/conector/health', [
            'Authorization' => 'Bearer '.self::TOKEN,
        ])->assertStatus(403);
    }

    public function test_health_returns_ok_with_valid_credentials(): void
    {
        $this->getJson('/api/v1/conector/health', $this->connectorHeaders())
            ->assertOk()
            ->assertJsonStructure(['status', 'system', 'version', 'timestamp'])
            ->assertJsonPath('status', 'ok');
    }

    public function test_empresas_returns_paginated_payload(): void
    {
        $response = $this->getJson('/api/v1/conector/empresas?page=1&pageSize=10', $this->connectorHeaders());
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'external_id',
                    'razao_social',
                    'status',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ],
            'total',
            'page',
            'pageSize',
        ]);
        $this->assertGreaterThan(0, $response->json('total'));
    }

    public function test_empresa_show_by_external_id(): void
    {
        $clinic = Clinic::query()->firstOrFail();

        $this->getJson('/api/v1/conector/empresas/org-'.$clinic->id, $this->connectorHeaders())
            ->assertOk()
            ->assertJsonPath('external_id', 'org-'.$clinic->id)
            ->assertJsonPath('razao_social', $clinic->billing_name ?: $clinic->name);
    }

    public function test_empresa_show_returns_404_for_unknown_external_id(): void
    {
        $this->getJson('/api/v1/conector/empresas/org-999999', $this->connectorHeaders())
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * @return array<string, string>
     */
    private function connectorHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.self::TOKEN,
            'X-Tenant-Id' => 't1',
            'Accept' => 'application/json',
        ];
    }
}
