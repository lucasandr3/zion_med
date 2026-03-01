<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NovoComentario extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FormSubmission $submission,
        public readonly User $commentedBy,
        public readonly string $body
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'novo_comentario',
            'icon'            => 'chat',
            'title'           => 'Novo comentário no protocolo',
            'body'            => "{$this->commentedBy->name} comentou no protocolo {$this->submission->protocol_number}: \"{$this->body}\"",
            'submission_id'   => $this->submission->id,
            'protocol_number' => $this->submission->protocol_number,
            'template_name'   => $this->submission->template?->name,
            'commented_by'    => $this->commentedBy->name,
            'url'             => route('protocolos.show', $this->submission),
        ];
    }
}
