<?php

namespace App\Listeners;

use App\Services\WebPushService;
use Illuminate\Notifications\Events\NotificationSent;

class SendWebPushOnDatabaseNotification
{
    public function __construct(private readonly WebPushService $webPush) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;
        if (! is_object($notifiable) || ! method_exists($notifiable, 'getKey')) {
            return;
        }

        if (! $notifiable instanceof \App\Models\User) {
            return;
        }

        $data = [];
        if (method_exists($event->notification, 'toArray')) {
            try {
                $data = (array) $event->notification->toArray($notifiable);
            } catch (\Throwable) {
                $data = [];
            }
        }

        $title = (string) ($data['title'] ?? 'Gestgo');
        $body = (string) ($data['body'] ?? $data['message'] ?? 'Você tem uma nova notificação.');
        // Evitar vazar dados sensíveis: truncar corpo.
        $body = mb_substr(trim(strip_tags($body)), 0, 120);

        $url = '/m';
        if (! empty($data['submission_id'])) {
            $url = '/m/documentos';
        } elseif (! empty($data['url']) && is_string($data['url']) && str_starts_with($data['url'], '/')) {
            $url = $data['url'];
        }

        $this->webPush->sendToUser($notifiable, [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'tag' => class_basename($event->notification),
        ]);
    }
}
