<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NovoProtocoloRecebido extends Notification
{
    use Queueable;

    public function __construct(public readonly FormSubmission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'novo_protocolo',
            'icon'             => 'inbox',
            'title'            => 'Novo protocolo recebido',
            'body'             => "Protocolo {$this->submission->protocol_number} foi enviado via formulário público.",
            'submission_id'    => $this->submission->id,
            'protocol_number'  => $this->submission->protocol_number,
            'template_name'    => $this->submission->template?->name,
            'submitter_name'   => $this->submission->submitter_name,
            'url'              => route('protocolos.show', ['submissao' => $this->submission->id]),
        ];
    }
}
