<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Events\AuditEvent;
use App\Models\Clinic;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\SubmissionAttachment;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        protected WebhookService $webhookService,
        protected ProtocolGeneratorService $protocolGenerator,
        protected TemplateVersionService $templateVersionService,
        protected DocumentSendService $documentSendService
    ) {}

    /**
     * @param  array<string, string>  $signatures  field_key => base64 image
     */
    public function createFromPublicForm(FormTemplate $template, array $data, array $files = [], array $signatures = [], ?Request $request = null): FormSubmission
    {
        if (! $template->public_enabled || ! $template->public_token) {
            throw ValidationException::withMessages(['formulário' => ['Formulário não disponível para preenchimento público.']]);
        }

        $signingChannel = $data['_signing_channel'] ?? 'web';
        $locale = $data['_locale'] ?? 'pt_BR';
        $timezone = $data['_timezone'] ?? config('app.timezone', 'America/Sao_Paulo');
        $acceptedTextAt = isset($data['_accepted_text_at']) ? now()->parse($data['_accepted_text_at']) : now();

        $submission = DB::transaction(function () use ($template, $data, $files, $signatures, $request, $signingChannel, $locale, $timezone, $acceptedTextAt) {
            $orgId = $template->organization_id ?? $template->clinic_id;
            $templateVersion = $this->templateVersionService->getOrCreateCurrentVersion($template);

            $submission = FormSubmission::withoutGlobalScopes()->create([
                'organization_id' => $orgId,
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

            foreach ($template->fields as $field) {
                $key = $field->name_key;
                if (! array_key_exists($key, $data) && $field->type !== 'signature' && $field->type !== 'file') {
                    continue;
                }
                $value = $data[$key] ?? null;
                if (is_array($value)) {
                    SubmissionValue::create([
                        'submission_id' => $submission->id,
                        'field_id' => $field->id,
                        'key' => $key,
                        'value_text' => null,
                        'value_json' => $value,
                    ]);
                } else {
                    SubmissionValue::create([
                        'submission_id' => $submission->id,
                        'field_id' => $field->id,
                        'key' => $key,
                        'value_text' => (string) $value,
                        'value_json' => null,
                    ]);
                }
            }

            $submission->load('values');
            $valuesKeyed = $submission->values->keyBy('key')->map(fn ($v) => $v->value_json ?? $v->value_text)->all();
            $documentSnapshot = [
                'protocol_number' => $submission->protocol_number,
                'template_version_id' => $templateVersion->id,
                'template_name' => $template->name,
                'template_description' => $template->description,
                'fields_snapshot' => $templateVersion->fields_snapshot,
                'values' => $valuesKeyed,
                'submitted_at' => $submission->submitted_at->toIso8601String(),
            ];
            $documentSnapshotHash = hash('sha256', json_encode($documentSnapshot));
            $documentHash = hash('sha256', implode('|', [
                $submission->protocol_number,
                (string) $templateVersion->id,
                $documentSnapshotHash,
                $submission->submitted_at->toIso8601String(),
            ]));
            $submission->update([
                'document_hash' => $documentHash,
                'document_snapshot_hash' => $documentSnapshotHash,
            ]);

            foreach ($files as $fieldKey => $file) {
                if ($file instanceof UploadedFile) {
                    $path = $file->store(
                        'organizations/' . $orgId . '/submissions/' . $submission->id,
                        'minio_attachments'
                    );
                    SubmissionAttachment::create([
                        'submission_id' => $submission->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'field_key' => $fieldKey,
                    ]);
                }
            }

            foreach ($signatures as $fieldKey => $signatureBase64) {
                if (! is_string($signatureBase64) || $signatureBase64 === '') {
                    continue;
                }
                $imagePath = $this->storeSignatureImage($orgId, $submission->id, $signatureBase64);
                $signedAt = now();
                $signedName = $data['_submitter_name'] ?? null;
                $evidencePayload = implode('|', [
                    (string) $submission->id,
                    $fieldKey,
                    $signedAt->toIso8601String(),
                    (string) $signedName,
                ]);
                $signatureHash = hash('sha256', $evidencePayload);
                $evidencePackage = [
                    'submission_id' => $submission->id,
                    'field_key' => $fieldKey,
                    'template_version_id' => $templateVersion->id,
                    'signed_name' => $signedName,
                    'signed_ip' => $request?->ip(),
                    'signed_user_agent' => $request ? Str::limit($request->userAgent(), 512) : null,
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
                    'form_template_version_id' => $templateVersion->id,
                    'image_path' => $imagePath,
                    'field_key' => $fieldKey,
                    'document_hash' => $documentHash,
                    'evidence_hash' => $evidenceHash,
                    'channel' => $signingChannel,
                    'status' => 'completed',
                    'accepted_text_at' => $acceptedTextAt,
                    'locale' => $locale,
                    'timezone' => $timezone,
                    'signed_name' => $signedName,
                    'signed_ip' => $request?->ip(),
                    'signed_user_agent' => $request ? Str::limit($request->userAgent(), 512) : null,
                    'signed_hash' => $signatureHash,
                    'signed_at' => $signedAt,
                ]);
            }

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
        $this->webhookService->dispatch($orgId, 'submission.created', $this->webhookPayload($submission));
        if ($submission->signatures()->exists()) {
            $this->webhookService->dispatch($orgId, 'submission.signed', $this->webhookPayload($submission));
        }

        return $submission;
    }

    protected function storeSignatureImage(int $organizationId, int $submissionId, string $base64): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw ValidationException::withMessages(['signature' => ['Assinatura inválida.']]);
        }
        $dir = 'organizations/' . $organizationId . '/signatures/' . $submissionId;
        $filename = Str::random(10) . '.png';
        $path = $dir . '/' . $filename;
        \Illuminate\Support\Facades\Storage::disk('minio_submissions')->put($path, $decoded);
        return $path;
    }

    protected function sendNotificationEmail(FormSubmission $submission): void
    {
        $clinic = $submission->organization ?? $submission->clinic;
        $email = $clinic->notification_email;
        if (! $email) {
            return;
        }
        try {
            Mail::raw(
                "Novo protocolo recebido.\nProtocolo: {$submission->protocol_number}\nFormulário: {$submission->template->name}\nAcesse o sistema para visualizar.",
                function ($message) use ($email, $submission) {
                    $message->to($email)
                        ->subject('Zion Med - Novo protocolo: ' . $submission->protocol_number);
                }
            );
        } catch (\Throwable) {
            // Log e continuar sem falhar o fluxo
        }
    }

    public function approve(FormSubmission $submission, string $status, ?string $comment, int $userId): void
    {
        $submission->update([
            'status' => $status === 'approved' ? SubmissionStatus::Approved : SubmissionStatus::Rejected,
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'review_comment' => $comment,
        ]);
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

        $reviewer = User::find($userId);
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
