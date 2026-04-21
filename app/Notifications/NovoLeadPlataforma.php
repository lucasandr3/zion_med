<?php

namespace App\Notifications;

use App\Models\DemonstrationRequest;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NovoLeadPlataforma extends Notification
{
    use Queueable;

    public function __construct(public readonly DemonstrationRequest $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'novo_lead',
            'icon'    => 'request_quote',
            'title'   => 'Novo lead na landing',
            'body'    => "{$this->lead->name} ({$this->lead->clinic}) solicitou demonstração.",
            'lead_id' => $this->lead->id,
            'url'     => FrontendUrl::plataformaLeads(),
        ];
    }
}
