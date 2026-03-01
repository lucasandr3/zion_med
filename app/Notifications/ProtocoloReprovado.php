<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProtocoloReprovado extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FormSubmission $submission,
        public readonly User $rejectedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'protocolo_reprovado',
            'icon'            => 'cancel',
            'title'           => 'Protocolo reprovado',
            'body'            => "O protocolo {$this->submission->protocol_number} foi reprovado por {$this->rejectedBy->name}." .
                ($this->submission->review_comment ? " Motivo: {$this->submission->review_comment}" : ''),
            'submission_id'   => $this->submission->id,
            'protocol_number' => $this->submission->protocol_number,
            'template_name'   => $this->submission->template?->name,
            'rejected_by'     => $this->rejectedBy->name,
            'comment'         => $this->submission->review_comment,
            'url'             => route('protocolos.show', $this->submission),
        ];
    }
}
