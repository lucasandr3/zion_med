<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendDemonstrationRequestN8nJob
{
    use Dispatchable;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly array $payload
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($this->webhookUrl, $this->payload);

            if ($response->failed()) {
                Log::warning('[DemonstrationRequest] Webhook n8n resposta não-sucesso', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 2000),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[DemonstrationRequest] Falha ao enviar webhook n8n: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
