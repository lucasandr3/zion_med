<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    public const EVENT_SUBSCRIPTION_CREATED = 'subscription.created';

    public const EVENT_COBRANCA = 'cobranca';
    public const EVENT_FATURAS_BOLETO = 'faturas_boleto';
    public const EVENT_AVISOS = 'avisos';

    /**
     * Verifica se a URL do webhook n8n está configurada.
     */
    public function isConfigured(): bool
    {
        $url = config('services.n8n_whatsapp.webhook_url');
        return ! empty($url) && filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Verifica se a clínica quer receber notificação WhatsApp para o tipo informado.
     */
    public function clinicWantsNotification(Clinic $clinic, string $eventType): bool
    {
        if (! $clinic->whatsapp_notifications_enabled ?? false) {
            return false;
        }
        return match ($eventType) {
            self::EVENT_COBRANCA => (bool) ($clinic->whatsapp_notify_cobranca ?? true),
            self::EVENT_FATURAS_BOLETO => (bool) ($clinic->whatsapp_notify_faturas_boleto ?? true),
            self::EVENT_AVISOS => (bool) ($clinic->whatsapp_notify_avisos ?? true),
            default => false,
        };
    }

    /**
     * Envia notificação de assinatura criada (confirmação) para o n8n.
     * Só envia se a clínica tiver WhatsApp habilitado e opção "Faturas/Boleto" ativa.
     */
    public function notifySubscriptionCreated(Clinic $clinic, array $subscriptionData): void
    {
        if (! $this->isConfigured()) {
            return;
        }
        if (! $this->clinicWantsNotification($clinic, self::EVENT_FATURAS_BOLETO)) {
            return;
        }

        $planName = $subscriptionData['plan_name'] ?? $subscriptionData['plan_key'] ?? 'Plano';
        $payload = [
            'event' => self::EVENT_SUBSCRIPTION_CREATED,
            'type' => self::EVENT_FATURAS_BOLETO,
            'clinic_id' => $clinic->id,
            'clinic_name' => $clinic->name,
            'plan_key' => $subscriptionData['plan_key'] ?? null,
            'plan_name' => $planName,
            'subscription_asaas_id' => $subscriptionData['asaas_subscription_id'] ?? null,
            'next_due_date' => $subscriptionData['next_due_date'] ?? null,
            'phone' => $this->normalizePhone($clinic->phone),
            'message' => sprintf(
                'Assinatura confirmada: %s – Plano %s. O boleto será enviado por e-mail.',
                $clinic->name,
                $planName
            ),
            'sent_at' => now()->toIso8601String(),
        ];

        $this->sendToN8n($payload);
    }

    /**
     * Envia payload para a URL do webhook n8n (POST JSON).
     */
    public function sendToN8n(array $payload): bool
    {
        $url = config('services.n8n_whatsapp.webhook_url');
        if (empty($url)) {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('WhatsApp/n8n webhook failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp/n8n webhook error', ['url' => $url, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizePhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return '55' . $digits;
        }
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }
        return '55' . $digits;
    }
}
