<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Models\SubmissionEvent;
use App\Services\PatientCopyService;
use App\Services\PdfService;
use App\Services\SubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Persiste o PDF e dispara notificações por e-mail fora do request HTTP de submit.
 */
class GenerateSubmissionPdfJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public int $backoff = 30;

    public function __construct(
        public int $submissionId,
        public bool $notifyClinicWithPdf = false,
        public bool $notifyPatientCopy = false,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->submissionId;
    }

    public function handle(
        PdfService $pdfService,
        SubmissionService $submissionService,
        PatientCopyService $patientCopyService,
    ): void {
        $submission = FormSubmission::withoutGlobalScopes()->find($this->submissionId);
        if (! $submission) {
            return;
        }

        if (! is_string($submission->pdf_disk_path) || $submission->pdf_disk_path === '') {
            $pdfService->persistSubmissionPdf($submission);
            $submission->refresh();
        }

        if ($this->notifyClinicWithPdf) {
            $alreadyClinic = SubmissionEvent::query()
                ->where('form_submission_id', $submission->id)
                ->where('type', 'clinic_protocol_emailed')
                ->exists();

            if (! $alreadyClinic) {
                $submissionService->sendClinicNotificationWithPdf($submission->fresh() ?? $submission);
            }
        }

        if ($this->notifyPatientCopy) {
            $fresh = $submission->fresh() ?? $submission;
            $token = is_string($fresh->patient_download_token) ? $fresh->patient_download_token : '';
            if ($token !== '' && $fresh->patient_copy_emailed_at === null) {
                $patientCopyService->sendPatientCopyEmail($fresh, $token);
            }
        }
    }
}
