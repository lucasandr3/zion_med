<?php

namespace App\Services;

use App\Models\DocumentSend;
use App\Models\FormTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class DocumentSendService
{
    public function __construct(
        protected PublicLinkService $publicLinkService
    ) {}

    /**
     * Envia o link do formulário por e-mail e registra o envio.
     */
    public function sendByEmail(
        FormTemplate $template,
        string $recipientEmail,
        ?string $recipientPhone = null,
        ?\DateTimeInterface $expiresAt = null
    ): DocumentSend {
        if (! $template->public_token || ! $template->public_enabled) {
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }

        $url = $this->publicLinkService->getPublicUrl($template);
        $expiresAt = $expiresAt ?? $template->public_token_expires_at?->toDateTimeImmutable();

        $send = DocumentSend::create([
            'organization_id' => $template->organization_id ?? $template->clinic_id,
            'form_template_id' => $template->id,
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
            'channel' => 'email',
            'sent_at' => now(),
            'expires_at' => $expiresAt,
            'public_token' => $template->public_token,
        ]);

        $clinicName = $template->organization?->name ?? 'Clínica';
        $templateName = $template->name;

        Mail::send([], [], function ($message) use ($recipientEmail, $url, $clinicName, $templateName) {
            $message->to($recipientEmail)
                ->subject("{$clinicName} - Documento para assinatura: {$templateName}")
                ->html(
                    "<p>Olá,</p>" .
                    "<p>{$clinicName} enviou o documento <strong>{$templateName}</strong> para sua assinatura.</p>" .
                    "<p><a href=\"{$url}\" style=\"display:inline-block;padding:12px 24px;background:#1e40af;color:#fff;text-decoration:none;border-radius:8px;\">Acessar e assinar</a></p>" .
                    "<p>Ou copie o link: {$url}</p>" .
                    "<p>— Zion Med</p>"
                );
        });

        return $send;
    }

    /**
     * Reenvia o link do documento (cria novo registro de envio e envia e-mail).
     */
    public function reenvio(DocumentSend $documentSend): DocumentSend
    {
        $template = $documentSend->formTemplate;
        if (! $template->public_token || ! $template->public_enabled) {
            throw new \RuntimeException('Link público do template não está ativo.');
        }

        $recipientEmail = $documentSend->recipient_email;
        if (! $recipientEmail) {
            throw new \RuntimeException('Envio original não possui e-mail do destinatário.');
        }

        return $this->sendByEmail(
            $template,
            $recipientEmail,
            $documentSend->recipient_phone,
            $documentSend->expires_at?->toDateTimeImmutable()
        );
    }

    /**
     * Associa uma submissão a um envio (quando o paciente submete e o e-mail coincide).
     */
    public function linkSubmissionToSend(DocumentSend $send, int $formSubmissionId): void
    {
        if ($send->form_submission_id === null) {
            $send->update(['form_submission_id' => $formSubmissionId]);
        }
    }

    /**
     * Cancela um envio (revoga o convite para assinar; não desativa o link do template).
     */
    public function cancel(DocumentSend $documentSend): void
    {
        if ($documentSend->form_submission_id !== null) {
            throw new \RuntimeException('Não é possível cancelar um envio já assinado.');
        }
        $documentSend->update(['cancelled_at' => now()]);
    }

    /**
     * Envia e-mail de lembrete (mesmo link) e marca reminded_at.
     */
    public function sendReminder(DocumentSend $documentSend): bool
    {
        if ($documentSend->form_submission_id || $documentSend->isCancelled() || $documentSend->isExpired()) {
            return false;
        }
        $template = $documentSend->formTemplate;
        if (! $template->public_token || ! $template->public_enabled) {
            return false;
        }
        $url = $this->publicLinkService->getPublicUrl($template);
        $clinicName = $template->organization?->name ?? 'Clínica';
        $templateName = $template->name;
        $email = $documentSend->recipient_email;
        if (! $email) {
            return false;
        }
        Mail::send([], [], function ($message) use ($email, $url, $clinicName, $templateName) {
            $message->to($email)
                ->subject("Lembrete: {$clinicName} - Documento pendente de assinatura: {$templateName}")
                ->html(
                    "<p>Olá,</p>" .
                    "<p>Lembrete: o documento <strong>{$templateName}</strong> de {$clinicName} ainda está aguardando sua assinatura.</p>" .
                    "<p><a href=\"{$url}\" style=\"display:inline-block;padding:12px 24px;background:#1e40af;color:#fff;text-decoration:none;border-radius:8px;\">Acessar e assinar</a></p>" .
                    "<p>— Zion Med</p>"
                );
        });
        $documentSend->update(['reminded_at' => now()]);
        return true;
    }

    /**
     * Envia o link por WhatsApp via webhook n8n (se configurado).
     */
    public function sendByWhatsApp(FormTemplate $template, string $recipientPhone, ?\DateTimeInterface $expiresAt = null): ?DocumentSend
    {
        $webhookUrl = config('services.n8n_whatsapp.webhook_url');
        if (empty($webhookUrl) || ! filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return null;
        }
        if (! $template->public_token || ! $template->public_enabled) {
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }
        $url = $this->publicLinkService->getPublicUrl($template);
        $expiresAt = $expiresAt ?? $template->public_token_expires_at?->toDateTimeImmutable();
        $send = DocumentSend::create([
            'organization_id' => $template->organization_id ?? $template->clinic_id,
            'form_template_id' => $template->id,
            'recipient_phone' => $recipientPhone,
            'channel' => 'whatsapp',
            'sent_at' => now(),
            'expires_at' => $expiresAt,
            'public_token' => $template->public_token,
        ]);
        $payload = [
            'phone' => preg_replace('/\D/', '', $recipientPhone),
            'message' => "Documento para assinatura: {$template->name}. Acesse: {$url}",
            'link' => $url,
            'template_name' => $template->name,
        ];
        try {
            Http::timeout(15)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            report($e);
        }
        return $send;
    }
}
