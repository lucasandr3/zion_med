<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Support\EsteticaStaffFieldRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfService
{
    public function streamSubmissionPdf(FormSubmission $submission): \Illuminate\Http\Response
    {
        $pdf = $this->buildPdf($submission);
        $filename = 'protocolo-' . ($submission->protocol_number ?? $submission->id) . '.pdf';

        return $pdf->stream($filename);
    }

    /** Retorna o conteúdo binário do PDF para inclusão em ZIP. */
    public function getSubmissionPdfContent(FormSubmission $submission): string
    {
        return $this->buildPdf($submission)->output();
    }

    private function buildPdf(FormSubmission $submission)
    {
        $submission->load(['template.fields', 'templateVersion', 'values', 'attachments', 'signatures', 'organization']);
        $clinic = $submission->organization ?? $submission->clinic;
        $logoUrl = $clinic->logo_url;
        $valuesKeyed = $submission->getValuesKeyed();
        $fields = $this->resolveFieldsForPdf($submission);
        $templateName = $submission->templateVersion?->name
            ?: $submission->template?->name
            ?: 'Documento';
        $templateVersionLabel = $submission->templateVersion
            ? 'v'.$submission->templateVersion->version
            : null;

        return Pdf::loadView('pdf.submission', [
            'submission' => $submission,
            'clinic' => $clinic,
            'logoUrl' => $logoUrl,
            'valuesKeyed' => $valuesKeyed,
            'fields' => $fields,
            'templateName' => $templateName,
            'templateVersionLabel' => $templateVersionLabel,
            'staffFields' => EsteticaStaffFieldRegistry::definitions($submission->template?->name ?? null),
        ])->setPaper('a4');
    }

    /**
     * Prefere o snapshot da versão assinada; cai no template vivo só como fallback legado.
     *
     * @return Collection<int, object>
     */
    private function resolveFieldsForPdf(FormSubmission $submission): Collection
    {
        $snapshot = $submission->document_snapshot['fields_snapshot']
            ?? $submission->templateVersion?->fields_snapshot
            ?? null;

        if (is_array($snapshot) && count($snapshot) > 0) {
            return collect($snapshot)
                ->sortBy(fn ($f) => (int) ($f['sort_order'] ?? 0))
                ->values()
                ->map(fn ($f) => (object) [
                    'type' => $f['type'] ?? 'text',
                    'label' => $f['label'] ?? '',
                    'name_key' => $f['name_key'] ?? '',
                    'required' => (bool) ($f['required'] ?? false),
                    'sort_order' => (int) ($f['sort_order'] ?? 0),
                ]);
        }

        return $submission->template?->fields ?? collect();
    }
}
