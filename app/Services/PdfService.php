<?php

namespace App\Services;

use App\Models\FormSubmission;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function streamSubmissionPdf(FormSubmission $submission): \Illuminate\Http\Response
    {
        $submission->load(['template.fields', 'values', 'attachments', 'signatures', 'organization']);
        $clinic = $submission->organization ?? $submission->clinic;
        $logoUrl = $clinic->logo_url;
        $valuesKeyed = $submission->getValuesKeyed();
        $fields = $submission->template->fields;

        $pdf = Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'clinic' => $clinic,
            'logoUrl' => $logoUrl,
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
        $logoUrl = $clinic->logo_url;
        $valuesKeyed = $submission->getValuesKeyed();
        $fields = $submission->template->fields;

        $pdf = Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'clinic' => $clinic,
            'logoUrl' => $logoUrl,
            'valuesKeyed' => $valuesKeyed,
            'fields' => $fields,
        ])->setPaper('a4');

        return $pdf->output();
    }
}
