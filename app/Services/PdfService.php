<?php

namespace App\Services;

use App\Models\FormSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function streamSubmissionPdf(FormSubmission $submission): \Illuminate\Http\Response
    {
        $submission->load(['template.fields', 'values', 'attachments', 'signatures', 'organization']);
        $clinic = $submission->organization ?? $submission->clinic;
        $logoPath = null;
        if ($clinic->logo_path && Storage::disk('public')->exists($clinic->logo_path)) {
            $logoPath = Storage::disk('public')->path($clinic->logo_path);
        }
        $valuesKeyed = $submission->getValuesKeyed();
        $fields = $submission->template->fields;

        $pdf = Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'clinic' => $clinic,
            'logoPath' => $logoPath,
            'valuesKeyed' => $valuesKeyed,
            'fields' => $fields,
        ])->setPaper('a4');

        $filename = 'protocolo-' . ($submission->protocol_number ?? $submission->id) . '.pdf';
        return $pdf->stream($filename);
    }

    /** Retorna o conteúdo binário do PDF para inclusão em ZIP. */
    public function getSubmissionPdfContent(FormSubmission $submission): string
    {
        $submission->load(['template.fields', 'values', 'attachments', 'signatures', 'organization']);
        $clinic = $submission->organization ?? $submission->clinic;
        $logoPath = null;
        if ($clinic->logo_path && Storage::disk('public')->exists($clinic->logo_path)) {
            $logoPath = Storage::disk('public')->path($clinic->logo_path);
        }
        $valuesKeyed = $submission->getValuesKeyed();
        $fields = $submission->template->fields;

        $pdf = Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'clinic' => $clinic,
            'logoPath' => $logoPath,
            'valuesKeyed' => $valuesKeyed,
            'fields' => $fields,
        ])->setPaper('a4');

        return $pdf->output();
    }
}
