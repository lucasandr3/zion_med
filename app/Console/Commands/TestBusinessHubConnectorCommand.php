<?php

namespace App\Console\Commands;

use App\Services\BusinessHubConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestBusinessHubConnectorCommand extends Command
{
    public function __construct(
        private readonly BusinessHubConfigService $businessHub,
    ) {
        parent::__construct();
    }

    protected $signature = 'conector:test-sync
                            {--base-url= : Base URL do conector (padrão: APP_URL/api/v1/conector)}
                            {--host-header= : Host HTTP (ex.: zion_med.test) para nginx interno Docker}
                            {--token= : Token Bearer (padrão: token em platform_settings)}
                            {--tenant=t1 : X-Tenant-Id enviado ao conector}';

    protected $description = 'Simula o pull do Business Hub contra a API do conector (health + entidades paginadas)';

    /**
     * @var list<string>
     */
    private array $resources = [
        'empresas',
        'clientes',
        'contatos',
        'leads',
        'assinaturas',
        'faturas',
    ];

    public function handle(): int
    {
        $token = (string) ($this->option('token') ?: ($this->businessHub->getConnectorToken() ?? ''));
        if ($token === '') {
            $this->error('Token não configurado. Gere em Plataforma → Configurações → Integrações ou use --token=');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) ($this->option('base-url') ?: (rtrim((string) config('app.url'), '/').'/api/v1/conector')), '/');
        $tenantId = (string) $this->option('tenant');

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-Id' => $tenantId,
            'Accept' => 'application/json',
            'X-Request-Id' => (string) str()->uuid(),
        ];

        $hostHeader = $this->option('host-header');
        if (is_string($hostHeader) && $hostHeader !== '') {
            $headers['Host'] = $hostHeader;
        }

        $http = Http::withHeaders($headers);

        $this->info("Conector: {$baseUrl}");
        if (isset($headers['Host'])) {
            $this->info("Host header: {$headers['Host']}");
        }
        $this->info("Tenant: {$tenantId}");
        $this->newLine();

        $health = $http->get("{$baseUrl}/health");
        if (! $health->successful()) {
            $this->error('Health check falhou: HTTP '.$health->status());
            $this->line($health->body());

            return self::FAILURE;
        }

        $this->components->info('Health OK — '.($health->json('system') ?? 'conector').' v'.($health->json('version') ?? '?'));
        $this->newLine();

        $totals = [];
        foreach ($this->resources as $resource) {
            $count = $this->countResource("{$baseUrl}/{$resource}", $headers);
            if ($count === null) {
                $this->error("Falha ao sincronizar /{$resource}");

                return self::FAILURE;
            }
            $totals[$resource] = $count;
            $this->line(sprintf('  %-12s %d registro(s)', "/{$resource}", $count));
        }

        $this->newLine();
        $this->components->info('Sync simulado concluído com sucesso.');
        $this->table(['Recurso', 'Total'], collect($totals)->map(fn (int $total, string $resource) => [$resource, $total])->values()->all());

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function countResource(string $url, array $headers): ?int
    {
        $page = 1;
        $pageSize = 100;
        $total = 0;
        $reportedTotal = null;

        do {
            $response = Http::withHeaders($headers)->get($url, [
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            if (! $response->successful()) {
                $this->line("  HTTP {$response->status()} em {$url}?page={$page}");

                return null;
            }

            $payload = $response->json();
            $items = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $reportedTotal = (int) ($payload['total'] ?? count($items));
            $total += count($items);
            $page++;
        } while ($page * $pageSize <= $reportedTotal && count($items) > 0);

        return $reportedTotal ?? $total;
    }
}
