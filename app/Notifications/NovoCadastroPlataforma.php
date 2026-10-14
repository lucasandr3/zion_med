<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NovoCadastroPlataforma extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?string $planKey = null,
        public readonly ?string $niche = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $niche = $this->niche ? " · {$this->niche}" : '';
        $plan = $this->planKey ? " · plano {$this->planKey}" : '';

        return [
            'type' => 'novo_cadastro',
            'icon' => 'person_add',
            'title' => 'Novo cadastro Gestgo',
            'body' => "{$this->organization->name}{$niche}{$plan}",
            'organization_id' => $this->organization->id,
            'url' => method_exists(FrontendUrl::class, 'plataformaCliente')
                ? FrontendUrl::plataformaCliente((int) $this->organization->id)
                : '/plataforma/clientes/'.$this->organization->id,
        ];
    }
}
