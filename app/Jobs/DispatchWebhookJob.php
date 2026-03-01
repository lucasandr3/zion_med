<?php

namespace App\Jobs;

use App\Models\ClinicWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public ClinicWebhook $webhook,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $payload = array_merge($this->payload, ['event' => $this->event]);
        $body = json_encode($payload);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $this->event,
            'User-Agent' => 'ZionMed-Webhooks/1.0',
        ];

        if (Str::length($this->webhook->secret ?? '') > 0) {
            $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $this->webhook->secret);
        }

        $delivery = WebhookDelivery::create([
            'clinic_webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'payload' => $payload,
            'attempt' => $this->attempts(),
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($this->webhook->url);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body' => Str::limit($response->body(), 2000),
                'delivered_at' => now(),
            ]);

            if ($response->successful()) {
                return;
            }
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        } catch (\Throwable $e) {
            $delivery->update([
                'response_code' => null,
                'error_message' => $e->getMessage(),
            ]);
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
            throw $e;
        }
    }
}
