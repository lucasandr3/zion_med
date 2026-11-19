<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\Person;
use App\Models\SubmissionEvent;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReconsentService
{
    public function __construct(
        private DocumentSendService $documentSendService,
    ) {}

    /**
     * Envia novo link de consentimento quando o protocolo anterior venceu.
     *
     * @return array{send_id: int, channel: string, message: string, sent_at: string}
     */
    public function solicitFromProtocol(FormSubmission $protocol, User $user, ?string $channel = null): array
    {
        $protocol->loadMissing(['template', 'person']);
        $this->assertEligibleForReconsent($protocol);

        $person = $protocol->person;
        if (! $person) {
            throw ValidationException::withMessages([
                'person' => ['Este protocolo não está vinculado a uma pessoa. Vincule a ficha antes de solicitar reconsentimento.'],
            ]);
        }

        $resolvedChannel = $this->resolveChannel($person, $channel);
        $dispatched = $this->dispatchSend($protocol->template, $person, $resolvedChannel);
        $send = $dispatched['send'];
        $manual = $dispatched['manual'];

        SubmissionEvent::create([
            'form_submission_id' => $protocol->id,
            'type' => 'reconsent_requested',
            'user_id' => $user->id,
            'body' => $manual
                ? 'Reconsentimento solicitado — link pronto para compartilhamento manual no WhatsApp.'
                : 'Reconsentimento solicitado — link enviado ao paciente.',
            'meta_json' => [
                'document_send_id' => $send->id,
                'channel' => $resolvedChannel,
                'delivery_mode' => $manual ? 'manual' : 'auto',
                'template_id' => $protocol->template_id,
                'person_id' => $person->id,
            ],
        ]);

        $result = [
            'send_id' => $send->id,
            'channel' => $resolvedChannel,
            'message' => $manual
                ? 'Link pronto para compartilhar no WhatsApp.'
                : ($resolvedChannel === 'whatsapp'
                    ? 'Link de reconsentimento enviado por WhatsApp.'
                    : 'Link de reconsentimento enviado por e-mail.'),
            'sent_at' => $send->sent_at?->toIso8601String(),
        ];

        if ($manual) {
            $result['delivery_mode'] = 'manual';
            $result['delivery_status'] = 'aguardando_envio_manual';
            $result['public_url'] = $manual['public_url'];
            $result['share_message'] = $manual['share_message'];
            $result['recipient_phone'] = $send->recipient_phone;
        }

        return $result;
    }

    /**
     * Solicita reconsentimento a partir da ficha da pessoa (protocolo vencido mais recente).
     *
     * @return array{send_id: int, channel: string, message: string, sent_at: string, protocol_id: int}
     */
    public function solicitFromPerson(Person $person, User $user, ?string $channel = null): array
    {
        $protocol = $this->findExpiredConsentProtocol($person);
        if (! $protocol) {
            throw ValidationException::withMessages([
                'consent' => ['Não há consentimento vencido para esta pessoa.'],
            ]);
        }

        $result = $this->solicitFromProtocol($protocol, $user, $channel);

        return array_merge($result, ['protocol_id' => $protocol->id]);
    }

    private function findExpiredConsentProtocol(Person $person): ?FormSubmission
    {
        return FormSubmission::query()
            ->where('person_id', $person->id)
            ->whereHas('template', function ($q) {
                $q->where('document_kind', 'consentimento')
                    ->orWhere('category', 'consentimento');
            })
            ->latest('submitted_at')
            ->get()
            ->first(fn (FormSubmission $s) => $s->isConsentExpired());
    }

    private function assertEligibleForReconsent(FormSubmission $protocol): void
    {
        $template = $protocol->template;
        if (! $template) {
            throw ValidationException::withMessages([
                'template' => ['Modelo do protocolo não encontrado.'],
            ]);
        }

        $kind = $template->document_kind ?: ($template->category === 'consentimento' ? 'consentimento' : 'ficha');
        if ($kind !== 'consentimento') {
            throw ValidationException::withMessages([
                'document_kind' => ['Reconsentimento só se aplica a documentos de consentimento informado.'],
            ]);
        }

        if (! $protocol->isConsentExpired()) {
            throw ValidationException::withMessages([
                'consent' => ['O consentimento ainda está dentro da validade ou não possui prazo de vencimento.'],
            ]);
        }
    }

    private function resolveChannel(Person $person, ?string $channel): string
    {
        $email = trim((string) ($person->email ?? ''));
        $phone = trim((string) ($person->phone ?? $person->phone_alt ?? ''));

        if ($channel === 'email') {
            if ($email === '') {
                throw ValidationException::withMessages([
                    'channel' => ['Cadastre um e-mail na ficha da pessoa ou escolha WhatsApp.'],
                ]);
            }

            return 'email';
        }

        if ($channel === 'whatsapp') {
            if ($phone === '') {
                throw ValidationException::withMessages([
                    'channel' => ['Cadastre um telefone na ficha da pessoa ou escolha e-mail.'],
                ]);
            }

            return 'whatsapp';
        }

        if ($email !== '') {
            return 'email';
        }
        if ($phone !== '') {
            return 'whatsapp';
        }

        throw ValidationException::withMessages([
            'contact' => ['Cadastre e-mail ou telefone na ficha da pessoa para enviar o link de reconsentimento.'],
        ]);
    }

    /**
     * @return array{send: \App\Models\DocumentSend, manual: ?array{send: \App\Models\DocumentSend, public_url: string, share_message: string}}
     */
    private function dispatchSend($template, Person $person, string $channel): array
    {
        if ($channel === 'whatsapp') {
            $phone = trim((string) ($person->phone ?? $person->phone_alt ?? ''));
            $send = $this->documentSendService->sendByWhatsApp(
                $template,
                $phone,
                null,
                $person->id,
                $person->name,
            );
            if ($send) {
                return ['send' => $send, 'manual' => null];
            }

            // MSG-03A — fallback manual (wa.me) quando Evolution não está disponível.
            $manual = $this->documentSendService->createManualWhatsAppShare(
                $template,
                $phone,
                null,
                $person->id,
                $person->name,
            );

            return ['send' => $manual['send'], 'manual' => $manual];
        }

        $send = $this->documentSendService->sendByEmail(
            $template,
            trim((string) $person->email),
            $person->phone ? trim((string) $person->phone) : null,
            null,
            $person->id,
            $person->name,
        );

        return ['send' => $send, 'manual' => null];
    }
}
