<?php

namespace App\Services;

use App\Models\ClinicWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Dispara um evento para todos os webhooks da clínica que escutam esse evento.
     */
    public function dispatch(int $clinicId, string $event, array $payload): void
    {
        $webhooks = ClinicWebhook::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (! $webhook->listensTo($event)) {
                continue;
            }
            \App\Jobs\DispatchWebhookJob::dispatch($webhook, $event, $payload);
        }
    }

    /**
     * Envia o payload para a URL do webhook com assinatura HMAC.
     */
    public function send(ClinicWebhook $webhook, string $event, array $payload): WebhookDelivery
    {
        $body = json_encode($payload);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $event,
            'User-Agent' => 'ZionMed-Webhooks/1.0',
        ];

        if (Str::length($webhook->secret ?? '') > 0) {
            $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);
        }

        $delivery = WebhookDelivery::create([
            'clinic_webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'attempt' => 1,
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body' => Str::limit($response->body(), 2000),
                'delivered_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'response_code' => null,
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $delivery;
    }
}
