<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CheckoutFalhouPlataforma extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?string $planKey = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $plan = $this->planKey ? " (plano {$this->planKey})" : '';

        return [
            'type' => 'checkout_falhou',
            'icon' => 'error',
            'title' => 'Checkout falhou',
            'body' => "{$this->organization->name}{$plan} não concluiu a assinatura.",
            'organization_id' => $this->organization->id,
            'url' => method_exists(FrontendUrl::class, 'plataformaCliente')
                ? FrontendUrl::plataformaCliente((int) $this->organization->id)
                : '/plataforma/clientes/'.$this->organization->id,
        ];
    }
}
