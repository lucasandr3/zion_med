<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Services\PdfService;
use App\Services\SubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSubmissionPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $submissionId,
        public bool $notifyClinicWithPdf = false,
    ) {}

    public function handle(PdfService $pdfService, SubmissionService $submissionService): void
    {
        $submission = FormSubmission::withoutGlobalScopes()->find($this->submissionId);
        if (! $submission) {
            return;
        }

        $pdfService->persistSubmissionPdf($submission);

        if ($this->notifyClinicWithPdf) {
            $submissionService->sendClinicNotificationWithPdf($submission->fresh());
        }
    }
}
