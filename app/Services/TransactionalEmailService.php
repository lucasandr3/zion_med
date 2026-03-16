<?php

namespace App\Services;

use App\Jobs\SendTransactionalEmailJob;
use Illuminate\Support\Facades\Mail;

/**
 * Serviço central para envio de e-mails transacionais (Resend) em background.
 * Use para notificações, confirmações, etc., com templates Blade.
 */
class TransactionalEmailService
{
    /**
     * Envia e-mail em background usando a fila (driver configurado em config/queue.php).
     * O driver de mail (ex.: Resend) é o definido em config/mail.php.
     *
     * @param  string  $to  E-mail do destinatário
     * @param  string  $subject  Assunto
     * @param  string  $view  Nome da view Blade (ex.: 'emails.welcome')
     * @param  array  $data  Dados para a view
     */
    public function send(string $to, string $subject, string $view, array $data = []): void
    {
        SendTransactionalEmailJob::dispatch($to, $subject, $view, $data);
    }

    /**
     * Envia e-mail de forma síncrona (sem fila). Use apenas quando precisar de envio imediato.
     */
    public function sendNow(string $to, string $subject, string $view, array $data = []): void
    {
        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
}
