<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FaturasVencidasPlataforma extends Notification
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
            ? 'Há 1 fatura vencida.'
            : "Há {$this->count} faturas vencidas.";

        return [
            'type'  => 'faturas_vencidas',
            'icon'  => 'payments',
            'title' => 'Faturas vencidas',
            'body'  => $msg,
            'count' => $this->count,
            'url'   => route('platform.payments.index'),
        ];
    }
}
