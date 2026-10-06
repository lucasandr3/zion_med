<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\Person;
use App\Models\SubmissionEvent;
use App\Support\MailBrand;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PatientCopyService
{
    public const TOKEN_TTL_HOURS = 72;

    public function issueDownloadToken(FormSubmission $submission): string
    {
        $token = Str::random(48);
        $submission->update([
            'patient_download_token' => $token,
            'patient_download_token_expires_at' => now()->addHours(self::TOKEN_TTL_HOURS),
        ]);

        SubmissionEvent::create([
            'form_submission_id' => $submission->id,
            'type' => 'patient_copy_token_issued',
            'user_id' => null,
            'body' => 'Token de cópia do paciente emitido.',
            'meta_json' => [
                'expires_at' => optional($submission->fresh()->patient_download_token_expires_at)?->toIso8601String(),
                'ttl_hours' => self::TOKEN_TTL_HOURS,
            ],
        ]);

        return $token;
    }

    public function findValidByToken(string $token): ?FormSubmission
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 16) {
            return null;
        }

        return FormSubmission::withoutGlobalScopes()
            ->where('patient_download_token', $token)
            ->where(function ($q) {
                $q->whereNull('patient_download_token_expires_at')
                    ->orWhere('patient_download_token_expires_at', '>', now());
            })
            ->first();
    }

    public function markDownloaded(FormSubmission $submission): void
    {
        $first = $submission->patient_copy_downloaded_at === null;
        if ($first) {
            $submission->update(['patient_copy_downloaded_at' => now()]);
        }

        SubmissionEvent::create([
            'form_submission_id' => $submission->id,
            'type' => 'patient_copy_downloaded',
            'user_id' => null,
            'body' => 'Paciente baixou a cópia em PDF.',
            'meta_json' => [
                'first_download' => $first,
                'downloaded_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function downloadUrl(string $token): string
    {
        $api = rtrim((string) config('app.url'), '/');

        return $api.'/api/v1/formulario-publico/copia/'.rawurlencode($token);
    }

    public function resolvePatientEmail(FormSubmission $submission): ?string
    {
        $fromSubmitter = strtolower(trim((string) ($submission->submitter_email ?? '')));
        if ($fromSubmitter !== '' && filter_var($fromSubmitter, FILTER_VALIDATE_EMAIL)) {
            return $fromSubmitter;
        }

        $submission->loadMissing('person');
        $person = $submission->person;
        if ($person instanceof Person) {
            $fromPerson = strtolower(trim((string) ($person->email ?? '')));
            if ($fromPerson !== '' && filter_var($fromPerson, FILTER_VALIDATE_EMAIL)) {
                return $fromPerson;
            }
        }

        return null;
    }

    public function sendPatientCopyEmail(FormSubmission $submission, string $downloadToken, ?string $overrideEmail = null): void
    {
        $email = $overrideEmail
            ? strtolower(trim($overrideEmail))
            : $this->resolvePatientEmail($submission);
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $submission->loadMissing(['template', 'organization']);
        $clinic = $submission->organization ?? $submission->clinic;
        $downloadUrl = $this->downloadUrl($downloadToken);

        try {
            $brand = (string) (config('mail.branding.product_name') ?: config('asaas.product_name') ?: config('app.name'));
            Mail::send(
                'emails.protocol-patient-copy',
                MailBrand::with([
                    'emailTitle' => 'Sua cópia do protocolo',
                    'protocolNumber' => $submission->protocol_number,
                    'templateName' => $submission->template?->name,
                    'clinicName' => $clinic?->name,
                    'downloadUrl' => $downloadUrl,
                    'expiresHours' => self::TOKEN_TTL_HOURS,
                ]),
                function ($message) use ($email, $submission, $brand) {
                    $message->to($email)
                        ->subject("{$brand} — cópia do protocolo {$submission->protocol_number}");
                }
            );
            $submission->update([
                'patient_copy_emailed_at' => now(),
                'submitter_email' => $submission->submitter_email ?: $email,
            ]);

            SubmissionEvent::create([
                'form_submission_id' => $submission->id,
                'type' => 'patient_copy_emailed',
                'user_id' => null,
                'body' => 'Link da cópia enviado por e-mail ao paciente.',
                'meta_json' => [
                    'email' => $email,
                    'emailed_at' => now()->toIso8601String(),
                    'override' => $overrideEmail !== null,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Reenvia a cópia para um e-mail informado na tela de sucesso (R7).
     *
     * @return array{ok: bool, message: string}
     */
    public function requestEmailDelivery(string $copyToken, string $email): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Informe um e-mail válido.'];
        }

        $submission = $this->findValidByToken($copyToken);
        if (! $submission) {
            return ['ok' => false, 'message' => 'Link de download inválido ou expirado.'];
        }

        if ($submission->patient_copy_emailed_at && $this->resolvePatientEmail($submission) === $email) {
            return ['ok' => true, 'message' => 'O link já havia sido enviado para este e-mail.'];
        }

        $token = (string) $submission->patient_download_token;
        $this->sendPatientCopyEmail($submission, $token, $email);

        if (! $submission->fresh()?->patient_copy_emailed_at) {
            return ['ok' => false, 'message' => 'Não foi possível enviar o e-mail. Tente novamente.'];
        }

        return ['ok' => true, 'message' => 'Enviamos o link da cópia para o e-mail informado.'];
    }

    /**
     * @return array{patient_download_token: string, patient_download_url: string, patient_download_expires_at: string|null}
     */
    public function issueAndNotify(FormSubmission $submission): array
    {
        $token = $this->issueDownloadToken($submission);
        $this->sendPatientCopyEmail($submission, $token);

        return [
            'patient_download_token' => $token,
            'patient_download_url' => $this->downloadUrl($token),
            'patient_download_expires_at' => optional($submission->fresh()->patient_download_token_expires_at)?->toIso8601String(),
        ];
    }
}
