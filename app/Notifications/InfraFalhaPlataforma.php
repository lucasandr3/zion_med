<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InfraFalhaPlataforma extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $failures
     */
    public function __construct(public readonly array $failures) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $list = implode('; ', $this->failures);

        return [
            'type' => 'infra_falha',
            'icon' => 'warning',
            'title' => 'Falha de infraestrutura',
            'body' => 'Health-check detectou: '.$list,
            'failures' => $this->failures,
            'url' => '/plataforma/status',
        ];
    }
}
