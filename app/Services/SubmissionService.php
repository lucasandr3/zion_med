<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Clinic;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\SubmissionAttachment;
use App\Models\SubmissionEvent;
use App\Models\SubmissionSignature;
use App\Models\SubmissionValue;
use App\Models\User;
use App\Notifications\NovoComentario;
use App\Notifications\NovoProtocoloRecebido;
use App\Notifications\ProtocoloAprovado;
use App\Notifications\ProtocoloReprovado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        protected AuditService $auditService,
        protected WebhookService $webhookService
    ) {}

    /**
     * @param  array<string, string>  $signatures  field_key => base64 image
     */
    public function createFromPublicForm(FormTemplate $template, array $data, array $files = [], array $signatures = []): FormSubmission
    {
        if (! $template->public_enabled || ! $template->public_token) {
            throw ValidationException::withMessages(['formulário' => ['Formulário não disponível para preenchimento público.']]);
        }

        $submission = DB::transaction(function () use ($template, $data, $files, $signatures) {
            $submission = FormSubmission::withoutGlobalScopes()->create([
                'clinic_id' => $template->clinic_id,
                'template_id' => $template->id,
                'status' => SubmissionStatus::Pending,
                'submitter_name' => $data['_submitter_name'] ?? null,
                'submitter_email' => $data['_submitter_email'] ?? null,
                'submitted_at' => now(),
            ]);
            $submission->update(['protocol_number' => 'ZM-' . $submission->id . '-' . now()->format('YmdHis')]);

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

            foreach ($files as $fieldKey => $file) {
                if ($file instanceof UploadedFile) {
                    $path = $file->store('submissions/' . $submission->id, 'public');
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
                $imagePath = $this->storeSignatureImage($submission->id, $signatureBase64);
                SubmissionSignature::create([
                    'submission_id' => $submission->id,
                    'image_path' => $imagePath,
                    'field_key' => $fieldKey,
                ]);
            }

            $this->auditService->log('submission.created', FormSubmission::class, $submission->id, [
                'protocol' => $submission->protocol_number,
                'template_id' => $template->id,
            ], $template->clinic_id, null);

            SubmissionEvent::create([
                'form_submission_id' => $submission->id,
                'type' => 'created',
                'user_id' => null,
                'body' => null,
            ]);

            $this->sendNotificationEmail($submission);

            return $submission;
        });

        // Notifica após o commit (fora da transação) para todos os usuários da clínica
        $submission->load('template');
        $this->notifyClinicUsersNewSubmission($submission);

        $this->webhookService->dispatch($submission->clinic_id, 'protocol.submitted', $this->webhookPayload($submission));

        return $submission;
    }

    protected function storeSignatureImage(int $submissionId, string $base64): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw ValidationException::withMessages(['signature' => ['Assinatura inválida.']]);
        }
        $dir = 'signatures/' . $submissionId;
        $filename = Str::random(10) . '.png';
        $path = $dir . '/' . $filename;
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded);
        return $path;
    }

    protected function sendNotificationEmail(FormSubmission $submission): void
    {
        $clinic = $submission->clinic;
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
        $this->auditService->log('submission.reviewed', FormSubmission::class, $submission->id, [
            'status' => $status,
            'comment' => $comment,
        ]);

        $event = $status === 'approved' ? 'protocol.approved' : 'protocol.rejected';
        $this->webhookService->dispatch($submission->clinic_id, $event, $this->webhookPayload($submission));

        $reviewer = User::find($userId);
        if ($reviewer) {
            $submission->load(['template']);
            $recipients = $this->getClinicUsers($submission->clinic_id)
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
            'clinic_id' => $submission->clinic_id,
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
        $this->auditService->log('submission.comment', FormSubmission::class, $submission->id, ['event_id' => $event->id], $submission->clinic_id, $userId);

        $commenter = User::find($userId);
        if ($commenter) {
            $submission->load(['template']);
            $recipients = $this->getClinicUsers($submission->clinic_id)
                ->reject(fn ($u) => $u->id === $userId);

            Notification::send($recipients, new NovoComentario($submission, $commenter, $body));
        }

        return $event;
    }

    /** Notifica todos os usuários da clínica sobre novo protocolo (chamado após commit). */
    protected function notifyClinicUsersNewSubmission(FormSubmission $submission): void
    {
        $recipients = $this->getClinicUsers($submission->clinic_id);
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
    protected function getClinicUsers(int $clinicId): \Illuminate\Support\Collection
    {
        return User::where('active', true)
            ->where(function ($q) use ($clinicId) {
                $q->where('clinic_id', $clinicId)
                    ->orWhereIn('role', ['super_admin']);
            })
            ->get();
    }
}
