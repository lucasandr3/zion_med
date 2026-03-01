<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProtocoloAprovado extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FormSubmission $submission,
        public readonly User $approvedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'protocolo_aprovado',
            'icon'            => 'check_circle',
            'title'           => 'Protocolo aprovado',
            'body'            => "O protocolo {$this->submission->protocol_number} foi aprovado por {$this->approvedBy->name}.",
            'submission_id'   => $this->submission->id,
            'protocol_number' => $this->submission->protocol_number,
            'template_name'   => $this->submission->template?->name,
            'approved_by'     => $this->approvedBy->name,
            'url'             => route('protocolos.show', $this->submission),
        ];
    }
}
