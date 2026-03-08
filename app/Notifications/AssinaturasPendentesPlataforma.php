<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssinaturasPendentesPlataforma extends Notification
{
    use Queueable;

    public function __construct(public readonly int $count) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $msg = $this->count === 1
            ? 'Há 1 assinatura pendente.'
            : "Há {$this->count} assinaturas pendentes.";

        return [
            'type'  => 'assinaturas_pendentes',
            'icon'  => 'receipt_long',
            'title' => 'Assinaturas pendentes',
            'body'  => $msg,
            'count' => $this->count,
            'url'   => route('platform.subscriptions.index'),
        ];
    }
}
