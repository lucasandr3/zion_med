<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Events\AuditEvent;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\SubmissionEvent;
use App\Models\DocumentSend;
use App\Models\SubmissionSignature;
use App\Models\SubmissionValue;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\NovoComentario;
use App\Notifications\NovoProtocoloRecebido;
use App\Notifications\ProtocoloAprovado;
use App\Notifications\ProtocoloReprovado;
use App\Support\EsteticaStaffFieldRegistry;
use App\Support\FrontendUrl;
use App\Support\MailBrand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Submissions\SubmissionPersistenceService;
use App\Services\Submissions\SubmissionPersonSyncService;
use App\Services\Submissions\SubmissionSignatureService;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        protected WebhookService $webhookService,
        protected ProtocolGeneratorService $protocolGenerator,
        protected TemplateVersionService $templateVersionService,
        protected DocumentSendService $documentSendService,
        protected SubmissionPersistenceService $persistenceService,
        protected SubmissionSignatureService $signatureService,
        protected SubmissionPersonSyncService $personSyncService,
    ) {}

    /**
     * @param  array<string, string>  $signatures  field_key => base64 image
     */
    public function createFromPublicForm(FormTemplate $template, array $data, array $files = [], array $signatures = [], ?Request $request = null, ?int $personId = null): FormSubmission
    {
        if (! $template->public_enabled || ! $template->public_token) {
            throw ValidationException::withMessages(['formulário' => ['Formulário não disponível para preenchimento público.']]);
        }

        $signingChannel = $data['_signing_channel'] ?? 'web';
        $locale = $data['_locale'] ?? 'pt_BR';
        $timezone = $data['_timezone'] ?? config('app.timezone', 'America/Sao_Paulo');
        $acceptedTextAt = isset($data['_accepted_text_at'])
            ? \Carbon\Carbon::parse((string) $data['_accepted_text_at'])
            : now();

        $submission = DB::transaction(function () use ($template, $data, $files, $signatures, $request, $signingChannel, $locale, $timezone, $acceptedTextAt, $personId) {
            $orgId = $template->organization_id ?? $template->clinic_id;
            $templateVersion = $this->templateVersionService->getOrCreateCurrentVersion($template);

            $submission = FormSubmission::withoutGlobalScopes()->create([
                'organization_id' => $orgId,
                'person_id' => $personId,
                'template_id' => $template->id,
                'template_version_id' => $templateVersion->id,
                'status' => SubmissionStatus::Pending,
                'submitter_name' => $data['_submitter_name'] ?? null,
                'submitter_email' => $data['_submitter_email'] ?? null,
                'submitted_at' => now(),
                'signing_channel' => $signingChannel,
                'signing_status' => 'completed',
                'locale' => $locale,
                'timezone' => $timezone,
                'accepted_text_at' => $acceptedTextAt,
            ]);
            $submission->update([
                'protocol_number' => $this->protocolGenerator->generate($template->organization_id ?? $template->clinic_id),
            ]);

            $this->persistenceService->writeFieldValues($submission, $template, $data);

            $documentHash = $this->persistenceService->applyDocumentHashes($submission, $template, $templateVersion);

            $this->persistenceService->storeAttachments((int) $orgId, $submission, $files);

            $this->signatureService->persistForSubmission(
                $submission,
                $templateVersion,
                $signatures,
                $data,
                $documentHash,
                $signingChannel,
                $locale,
                $timezone,
                $acceptedTextAt,
                $request,
            );

            Event::dispatch(new AuditEvent('submission.created', FormSubmission::class, $submission->id, [
                'protocol' => $submission->protocol_number,
                'template_id' => $template->id,
            ], $template->organization_id ?? $template->clinic_id, null));

            SubmissionEvent::create([
                'form_submission_id' => $submission->id,
                'type' => 'created',
                'user_id' => null,
                'body' => null,
                'meta_json' => [
                    'channel' => $signingChannel,
                    'locale' => $locale,
                    'timezone' => $timezone,
                ],
            ]);

            if (! $submission->person_id) {
                $personAuto = $this->personSyncService->ensurePersonFromPublicForm($submission, $template, $data);
                if ($personAuto) {
                    $submission->update(['person_id' => $personAuto->id]);
                }
            }

            $this->sendNotificationEmail($submission);

            return $submission;
        });

        // Notifica após o commit (fora da transação) para todos os usuários da clínica
        $submission->load('template');
        $this->notifyClinicUsersNewSubmission($submission);

        $orgId = $submission->organization_id ?? $submission->clinic_id;
        if ($submission->submitter_email) {
            $documentSend = DocumentSend::where('organization_id', $orgId)
                ->where('form_template_id', $submission->template_id)
                ->where('recipient_email', $submission->submitter_email)
                ->whereNull('form_submission_id')
                ->orderByDesc('sent_at')
                ->first();
            if ($documentSend) {
                $this->documentSendService->linkSubmissionToSend($documentSend, $submission->id);
            }
        }
        if ($submission->person_id) {
            $documentSendByPerson = DocumentSend::where('organization_id', $orgId)
                ->where('form_template_id', $submission->template_id)
                ->where('person_id', $submission->person_id)
                ->whereNull('form_submission_id')
                ->orderByDesc('sent_at')
                ->first();
            if ($documentSendByPerson) {
                $this->documentSendService->linkSubmissionToSend($documentSendByPerson, $submission->id);
            }
        }
        $this->webhookService->dispatch($orgId, 'submission.created', $this->webhookPayload($submission));
        if ($submission->signatures()->exists()) {
            $this->webhookService->dispatch($orgId, 'submission.signed', $this->webhookPayload($submission));
        }

        return $submission;
    }

    protected function sendNotificationEmail(FormSubmission $submission): void
    {
        $clinic = $submission->organization ?? $submission->clinic;
        $email = $clinic->notification_email;
        if (! $email) {
            return;
        }
        try {
            $brand = (string) (config('mail.branding.product_name') ?: config('asaas.product_name') ?: config('app.name'));
            Mail::send(
                'emails.protocol-new',
                MailBrand::with([
                    'emailTitle' => 'Novo protocolo',
                    'protocolNumber' => $submission->protocol_number,
                    'templateName' => $submission->template->name,
                    'submitterName' => $submission->submitter_name,
                    'dashboardUrl' => FrontendUrl::protocoloDetalhe($submission),
                ]),
                function ($message) use ($email, $submission, $brand) {
                    $message->to($email)
                        ->subject("{$brand} — novo protocolo: {$submission->protocol_number}");
                }
            );
        } catch (\Throwable) {
            // Log e continuar sem falhar o fluxo
        }
    }

    public function approve(FormSubmission $submission, string $status, ?string $comment, int $userId, ?Request $request = null): void
    {
        $submission->update([
            'status' => $status === 'approved' ? SubmissionStatus::Approved : SubmissionStatus::Rejected,
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'review_comment' => $comment,
        ]);

        $reviewer = User::find($userId);

        if ($status === 'approved' && $reviewer) {
            try {
                $this->attachApproverElectronicSignature($submission, $reviewer, $request);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($status === 'approved' && $submission->person_id) {
            try {
                $this->personSyncService->syncPersonFromApprovedSubmission($submission);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        SubmissionEvent::create([
            'form_submission_id' => $submission->id,
            'type' => $status === 'approved' ? 'approved' : 'rejected',
            'user_id' => $userId,
            'body' => $comment,
        ]);
        Event::dispatch(new AuditEvent('submission.reviewed', FormSubmission::class, $submission->id, [
            'status' => $status,
            'comment' => $comment,
        ], $submission->organization_id ?? $submission->clinic_id, $userId));

        $event = $status === 'approved' ? 'submission.approved' : 'submission.rejected';
        $this->webhookService->dispatch($submission->organization_id ?? $submission->clinic_id, $event, $this->webhookPayload($submission));

        if ($reviewer) {
            $submission->load(['template']);
            $recipients = $this->getClinicUsers($submission->organization_id ?? $submission->clinic_id)
                ->reject(fn ($u) => $u->id === $userId);

            if ($status === 'approved') {
                Notification::send($recipients, new ProtocoloAprovado($submission, $reviewer));
            } else {
                Notification::send($recipients, new ProtocoloReprovado($submission, $reviewer));
            }
        }
    }

    /**
     * Copia a assinatura eletrônica gravada no perfil do revisor para o primeiro espaço de assinatura
     * do modelo ainda vazio (preferindo campos cuja etiqueta sugira profissional da clínica).
     */
    private function attachApproverElectronicSignature(FormSubmission $submission, User $reviewer, ?Request $request): void
    {
        $storagePath = $reviewer->electronic_signature_path ?? null;
        if ($storagePath === null || $storagePath === '') {
            return;
        }

        $disk = Storage::disk('minio_submissions');
        try {
            if (! $disk->exists($storagePath)) {
                return;
            }
            $bytes = $disk->get($storagePath);
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        if ($bytes === null || $bytes === '') {
            return;
        }

        $submission->loadMissing(['template.fields', 'signatures']);
        $template = $submission->template;
        if (! $template) {
            return;
        }

        $orgId = (int) ($submission->organization_id ?? $submission->clinic_id ?? 0);
        if ($orgId < 1) {
            return;
        }

        /** @var Collection<int, FormField> $slots */
        $slots = collect($template->fields)
            ->filter(fn ($f) => $f instanceof FormField && $f->type === 'signature')
            ->sortBy(fn ($f) => $f instanceof FormField ? ($f->sort_order ?? 0) : 0)
            ->values();

        $filledKeys = $submission->signatures->pluck('field_key')->filter()->unique()->all();
        /** @var Collection<int, FormField> $empty */
        $empty = $slots->filter(fn ($f) => $f instanceof FormField && ! in_array($f->name_key, $filledKeys, true));
        if ($empty->isEmpty()) {
            return;
        }

        $chosen = $this->pickProfessionalSignatureSlot($empty);
        if (! $chosen instanceof FormField) {
            return;
        }

        try {
            $filename = 'approver-' . Str::random(10) . '.png';
            $relativeDir = 'organizations/' . $orgId . '/signatures/' . $submission->id;
            $imagePath = $relativeDir . '/' . $filename;
            $disk->put($imagePath, $bytes);
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $signedAt = now();
        $fieldKey = (string) $chosen->name_key;
        $signedName = $reviewer->name;
        $locale = $submission->locale ?: 'pt_BR';
        $timezone = $submission->timezone ?: config('app.timezone', 'America/Sao_Paulo');
        $acceptedTextAt = now();
        $signingChannel = 'approval';

        $evidencePayload = implode('|', [
            (string) $submission->id,
            $fieldKey,
            $signedAt->toIso8601String(),
            (string) $signedName,
        ]);
        $signatureHash = hash('sha256', $evidencePayload);
        $documentHash = (string) ($submission->document_hash ?? '');
        $templateVersionId = $submission->template_version_id;

        $evidencePackage = [
            'submission_id' => $submission->id,
            'field_key' => $fieldKey,
            'template_version_id' => $templateVersionId,
            'signed_name' => $signedName,
            'signed_ip' => $request?->ip(),
            'signed_user_agent' => $request ? Str::limit((string) $request->userAgent(), 512) : null,
            'signed_at' => $signedAt->toIso8601String(),
            'signed_hash' => $signatureHash,
            'document_hash' => $documentHash,
            'channel' => $signingChannel,
            'locale' => $locale,
            'timezone' => $timezone,
            'accepted_text_at' => $acceptedTextAt->toIso8601String(),
        ];
        $evidenceHash = hash('sha256', json_encode($evidencePackage));

        SubmissionSignature::create([
            'submission_id' => $submission->id,
            'form_template_version_id' => $templateVersionId,
            'image_path' => $imagePath,
            'field_key' => $fieldKey,
            'document_hash' => $documentHash !== '' ? $documentHash : null,
            'evidence_hash' => $evidenceHash,
            'channel' => $signingChannel,
            'status' => 'completed',
            'accepted_text_at' => $acceptedTextAt,
            'locale' => $locale,
            'timezone' => $timezone,
            'signed_name' => $signedName,
            'signed_ip' => $request?->ip(),
            'signed_user_agent' => $request ? Str::limit((string) $request->userAgent(), 512) : null,
            'signed_hash' => $signatureHash,
            'signed_at' => $signedAt,
        ]);
    }

    /**
     * @param  Collection<int, FormField>  $emptyFields
     */
    private function pickProfessionalSignatureSlot(Collection $emptyFields): ?FormField
    {
        if ($emptyFields->isEmpty()) {
            return null;
        }

        $positive = ['profissional', 'responsável', 'responsavel', 'clínica', 'clinica', 'equipe', 'médico', 'medico', 'dr.', 'dra.', 'doctor', 'prestador', 'cirurgião', 'cirurgiao'];
        $negativePhrases = ['paciente', 'cliente', 'titular', 'genitor', 'genitora', 'acompanhante', 'assistido', 'responsável legal', 'responsavel legal'];

        $best = null;
        $bestScore = PHP_INT_MIN;
        foreach ($emptyFields as $field) {
            $haystack = mb_strtolower((string) $field->name_key . ' ' . (string) $field->label);
            $score = 0;
            foreach ($positive as $word) {
                if (str_contains($haystack, $word)) {
                    $score += 3;
                }
            }
            foreach ($negativePhrases as $word) {
                if (str_contains($haystack, $word)) {
                    $score -= 5;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $field;
            }
        }

        if ($bestScore > 0 && $best instanceof FormField) {
            return $best;
        }

        /** @var FormField|null */
        return $emptyFields->last();
    }

    protected function webhookPayload(FormSubmission $submission): array
    {
        $submission->loadMissing('template');
        return [
            'protocol_id' => $submission->id,
            'protocol_number' => $submission->protocol_number,
            'template_id' => $submission->template_id,
            'template_name' => $submission->template?->name,
            'status' => $submission->status->value,
            'submitter_name' => $submission->submitter_name,
            'submitter_email' => $submission->submitter_email,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'approved_at' => $submission->approved_at?->toIso8601String(),
            'organization_id' => $submission->organization_id ?? $submission->clinic_id,
            'clinic_id' => $submission->organization_id ?? $submission->clinic_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function addComment(FormSubmission $submission, int $userId, string $body): SubmissionEvent
    {
        $event = SubmissionEvent::create([
            'form_submission_id' => $submission->id,
            'type' => 'comment',
            'user_id' => $userId,
            'body' => $body,
        ]);
        Event::dispatch(new AuditEvent('submission.comment', FormSubmission::class, $submission->id, ['event_id' => $event->id], $submission->clinic_id, $userId));

        $commenter = User::find($userId);
        if ($commenter) {
            $submission->load(['template']);
            $recipients = $this->getClinicUsers($submission->organization_id ?? $submission->clinic_id)
                ->reject(fn ($u) => $u->id === $userId);

            Notification::send($recipients, new NovoComentario($submission, $commenter, $body));
        }

        return $event;
    }

    /**
     * Persiste valores preenchidos pela equipe no protocolo (campos definidos em EsteticaStaffFieldsPack).
     *
     * @param  array<string, mixed>  $values
     */
    public function syncStaffFieldValues(FormSubmission $submission, array $values, int $userId): void
    {
        $submission->loadMissing('template');
        $allowed = EsteticaStaffFieldRegistry::allowedKeys($submission->template?->name);
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'values' => ['Este modelo não possui campos internos de equipe configurados.'],
            ]);
        }

        $allowedSet = array_flip($allowed);

        DB::transaction(function () use ($submission, $values, $allowedSet) {
            foreach ($values as $key => $raw) {
                $key = (string) $key;
                if (! isset($allowedSet[$key])) {
                    continue;
                }

                $existing = SubmissionValue::where('submission_id', $submission->id)
                    ->where('key', $key)
                    ->orderBy('id')
                    ->first();

                if (is_array($raw)) {
                    $payload = [
                        'field_id' => null,
                        'value_text' => null,
                        'value_json' => $raw,
                    ];
                } else {
                    $str = $raw === null ? '' : trim((string) $raw);
                    $payload = [
                        'field_id' => null,
                        'value_text' => $str === '' ? null : $str,
                        'value_json' => null,
                    ];
                }

                if ($existing) {
                    $existing->update($payload);
                } else {
                    SubmissionValue::create(array_merge($payload, [
                        'submission_id' => $submission->id,
                        'key' => $key,
                    ]));
                }
            }
        });

        Event::dispatch(new AuditEvent('submission.staff_values', FormSubmission::class, $submission->id, [
            'keys' => array_keys($values),
        ], $submission->organization_id ?? $submission->clinic_id, $userId));
    }

    /** Notifica todos os usuários da clínica sobre novo protocolo (chamado após commit). */
    protected function notifyClinicUsersNewSubmission(FormSubmission $submission): void
    {
        $recipients = $this->getClinicUsers($submission->organization_id ?? $submission->clinic_id);
        if ($recipients->isEmpty()) {
            return;
        }
        try {
            Notification::send($recipients, new NovoProtocoloRecebido($submission));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Todos os usuários ativos da clínica (recebem todas as notificações). Inclui SuperAdmins. */
    protected function getClinicUsers(int $organizationId): \Illuminate\Support\Collection
    {
        return User::withoutGlobalScopes()
            ->where('active', true)
            ->where(function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId)
                    ->orWhereIn('role', ['super_admin']);
            })
            ->get();
    }
}
