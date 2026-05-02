<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Events\AuditEvent;
use App\Models\Clinic;
use App\Models\Person;
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
use App\Support\EsteticaStaffFieldRegistry;
use App\Support\FrontendUrl;
use App\Support\PersonPiiHasher;
use App\Support\MailBrand;
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

            if (! $submission->person_id) {
                $personAuto = $this->ensurePersonFromPublicForm($submission, $template, $data);
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

    public function approve(FormSubmission $submission, string $status, ?string $comment, int $userId): void
    {
        $submission->update([
            'status' => $status === 'approved' ? SubmissionStatus::Approved : SubmissionStatus::Rejected,
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'review_comment' => $comment,
        ]);

        if ($status === 'approved' && $submission->person_id) {
            try {
                $this->syncPersonFromApprovedSubmission($submission);
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

    /**
     * @return array<string, string>
     */
    protected function submissionValuesAsFlatData(FormSubmission $submission): array
    {
        $data = [];
        foreach ($submission->values as $v) {
            if ($v->value_text !== null && trim((string) $v->value_text) !== '') {
                $data[$v->key] = trim((string) $v->value_text);

                continue;
            }
            if ($v->value_json !== null && ! is_array($v->value_json)) {
                $s = trim((string) $v->value_json);
                if ($s !== '') {
                    $data[$v->key] = $s;
                }
            }
        }

        return $data;
    }

    /**
     * Atualiza a ficha da pessoa vinculada com nome/contatos/CPF/nascimento extraídos do protocolo aprovado.
     */
    protected function syncPersonFromApprovedSubmission(FormSubmission $submission): void
    {
        if (! $submission->person_id) {
            return;
        }

        $orgId = $submission->organization_id ?? $submission->clinic_id;
        if (! $orgId) {
            return;
        }

        $person = Person::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->find($submission->person_id);

        if (! $person) {
            return;
        }

        $submission->loadMissing(['template.fields', 'values']);

        if (! $submission->template) {
            return;
        }

        $flat = $this->submissionValuesAsFlatData($submission);
        $fields = $this->extractPersonFieldsFromSubmission($submission->template, $flat, $submission);

        $nome = trim((string) ($fields['name'] ?? ''));
        $cpfNorm = $this->normalizeCpf($fields['cpf'] ?? null);
        $email = $this->normalizeEmail($fields['email'] ?? null);
        $phone = $this->normalizePhone($fields['phone'] ?? null);
        $birth = $this->normalizeBirthDate($fields['birth_date'] ?? null);

        $updates = [];
        if ($nome !== '') {
            $updates['name'] = $nome;
        }
        if ($phone) {
            $updates['phone'] = $phone;
        }
        if ($email) {
            $updates['email'] = $email;
        }
        if ($cpfNorm) {
            $updates['cpf'] = $cpfNorm;
        }
        if ($birth) {
            $updates['birth_date'] = $birth;
        }

        if ($updates === []) {
            return;
        }

        $person->fill($updates)->save();
    }

    /**
     * Cria (ou reaproveita) uma pessoa a partir dos dados de um protocolo público
     * quando o modelo NÃO exige vínculo com ficha. Usa heurística de CPF/e-mail/nome+nascimento
     * para evitar duplicidade dentro da mesma organização.
     */
    protected function ensurePersonFromPublicForm(FormSubmission $submission, FormTemplate $template, array $data): ?Person
    {
        $orgId = $submission->organization_id ?? $submission->clinic_id;
        if (! $orgId) {
            return null;
        }

        $fields = $this->extractPersonFieldsFromSubmission($template, $data, $submission);
        $nome = trim((string) ($fields['name'] ?? ''));
        if ($nome === '') {
            return null; // sem nome, não faz sentido criar a ficha
        }

        $cpfNorm = $this->normalizeCpf($fields['cpf'] ?? null);
        $email = $this->normalizeEmail($fields['email'] ?? null);
        $phone = $this->normalizePhone($fields['phone'] ?? null);
        $birth = $this->normalizeBirthDate($fields['birth_date'] ?? null);

        $query = Person::withoutGlobalScopes()->where('organization_id', $orgId);

        $match = null;
        if ($cpfNorm) {
            $match = (clone $query)->where('cpf_hash', PersonPiiHasher::cpf($cpfNorm))->first();
        }
        if (! $match && $email) {
            $match = (clone $query)->where('email_hash', PersonPiiHasher::email($email))->first();
        }
        if (! $match && $birth) {
            $match = (clone $query)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($nome)])
                ->whereDate('birth_date', $birth)
                ->first();
        }

        if ($match) {
            $updates = array_filter([
                'phone' => $match->phone ?: $phone,
                'email' => $match->email ?: $email,
                'cpf' => $match->cpf ?: $cpfNorm,
                'birth_date' => $match->birth_date ?: $birth,
            ], fn ($v) => $v !== null && $v !== '');
            if ($updates) {
                $match->fill($updates)->save();
            }
            return $match;
        }

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $orgId,
            'code' => '_tmp_' . bin2hex(random_bytes(8)),
            'name' => $nome,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth,
            'cpf' => $cpfNorm,
            'status' => 'active',
        ]);
        $person->update([
            'code' => 'P-' . str_pad((string) $person->id, 6, '0', STR_PAD_LEFT),
        ]);

        return $person->fresh();
    }

    /**
     * Extrai campos "pessoa" do payload do formulário. Usa `name_key` dos campos do template
     * com correspondência comum (pt/en) para localizar nome, cpf, email, telefone, nascimento.
     */
    protected function extractPersonFieldsFromSubmission(FormTemplate $template, array $data, FormSubmission $submission): array
    {
        $groups = [
            'name'       => ['nome', 'nome_completo', 'name', 'fullname', 'full_name', 'paciente', 'cliente'],
            'cpf'        => ['cpf', 'documento', 'doc'],
            'email'      => ['email', 'e_mail', 'e-mail', 'correio_eletronico'],
            'phone'      => ['telefone', 'celular', 'whatsapp', 'wa', 'phone', 'mobile', 'contato_telefone'],
            'birth_date' => ['data_nascimento', 'dt_nascimento', 'nascimento', 'birth_date', 'birthdate', 'data_de_nascimento'],
        ];

        $findValueByKey = function (string $needle) use ($data): ?string {
            $needleNorm = strtolower($needle);
            foreach ($data as $k => $v) {
                if (! is_string($k)) continue;
                if (strtolower($k) === $needleNorm && is_scalar($v) && trim((string) $v) !== '') {
                    return (string) $v;
                }
            }
            return null;
        };

        $result = ['name' => null, 'cpf' => null, 'email' => null, 'phone' => null, 'birth_date' => null];

        foreach ($template->fields as $field) {
            $keyNorm = strtolower((string) $field->name_key);
            foreach ($groups as $target => $candidates) {
                if ($result[$target] !== null) continue;
                foreach ($candidates as $cand) {
                    if ($keyNorm === $cand || str_contains($keyNorm, $cand)) {
                        $val = $findValueByKey($field->name_key);
                        if ($val !== null) {
                            $result[$target] = $val;
                            break 2;
                        }
                    }
                }
            }
        }

        // Fallbacks a partir do próprio submission
        if (! $result['name'] && ! empty($submission->submitter_name)) {
            $result['name'] = $submission->submitter_name;
        }
        if (! $result['email'] && ! empty($submission->submitter_email)) {
            $result['email'] = $submission->submitter_email;
        }

        return $result;
    }

    protected function normalizeCpf(?string $raw): ?string
    {
        if (! $raw) return null;
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        return strlen($digits) === 11 ? $digits : null;
    }

    protected function normalizeEmail(?string $raw): ?string
    {
        if (! $raw) return null;
        $email = strtolower(trim($raw));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function normalizePhone(?string $raw): ?string
    {
        if (! $raw) return null;
        $s = trim($raw);
        return $s !== '' ? Str::limit($s, 50, '') : null;
    }

    protected function normalizeBirthDate(?string $raw): ?string
    {
        if (! $raw) return null;
        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
