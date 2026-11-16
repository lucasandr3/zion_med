<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    public function isConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'))
            && class_exists(\Minishlink\WebPush\WebPush::class);
    }

    public function publicKey(): ?string
    {
        $key = config('webpush.vapid.public_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * @param  array{title?: string, body?: string, url?: string, tag?: string}  $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $subs = PushSubscription::query()->where('user_id', $user->id)->get();
        if ($subs->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => (string) config('webpush.vapid.subject'),
                'publicKey' => (string) config('webpush.vapid.public_key'),
                'privateKey' => (string) config('webpush.vapid.private_key'),
            ],
        ];

        /** @var \Minishlink\WebPush\WebPush $webPush */
        $webPush = new (\Minishlink\WebPush\WebPush::class)($auth);
        $webPush->setReuseVAPIDHeaders(true);

        $title = (string) ($payload['title'] ?? 'Gestgo');
        $body = (string) ($payload['body'] ?? '');
        $json = json_encode([
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/icons/icon-192x192.png',
                'badge' => '/icons/icon-72x72.png',
                'data' => [
                    'onActionClick' => [
                        'default' => ['operation' => 'openWindow', 'url' => $payload['url'] ?? '/m'],
                    ],
                ],
                'tag' => $payload['tag'] ?? 'gestgo',
            ],
        ], JSON_UNESCAPED_UNICODE);

        foreach ($subs as $sub) {
            try {
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                ]);
                $webPush->queueNotification($subscription, $json ?: '{}');
            } catch (\Throwable $e) {
                Log::warning('webpush.queue_failed', [
                    'subscription_id' => $sub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($webPush->flush() as $report) {
            if (! $report->isSuccess()) {
                $endpoint = $report->getRequest()?->getUri()?->__toString();
                $code = $report->getResponse()?->getStatusCode();
                if (in_array($code, [404, 410], true) && $endpoint) {
                    PushSubscription::query()->where('endpoint', $endpoint)->delete();
                }
                Log::info('webpush.delivery_failed', [
                    'endpoint' => $endpoint,
                    'reason' => $report->getReason(),
                ]);
            } else {
                $endpoint = $report->getRequest()?->getUri()?->__toString();
                if ($endpoint) {
                    PushSubscription::query()
                        ->where('endpoint', $endpoint)
                        ->update(['last_used_at' => now()]);
                }
            }
        }
    }
}
