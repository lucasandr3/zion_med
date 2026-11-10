<?php

namespace App\Notifications;

use App\Models\ProductFeedback;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NovoFeedbackPlataforma extends Notification
{
    use Queueable;

    public function __construct(public readonly ProductFeedback $feedback) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $tipo = $this->feedback->type === 'bug' ? 'Problema/bug' : 'Sugestão';
        $nome = $this->feedback->user?->name ?? 'Usuário';

        return [
            'type' => 'novo_feedback',
            'icon' => $this->feedback->type === 'bug' ? 'bug_report' : 'lightbulb',
            'title' => "Novo feedback: {$tipo}",
            'body' => "{$nome}: {$this->feedback->title}",
            'feedback_id' => $this->feedback->id,
            'url' => FrontendUrl::plataformaFeedback(),
        ];
    }
}
