<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Support\EsteticaStaffFieldRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function streamSubmissionPdf(FormSubmission $submission): \Illuminate\Http\Response
    {
        $stored = $this->readStoredPdf($submission);
        $filename = 'protocolo-' . ($submission->protocol_number ?? $submission->id) . '.pdf';

        if ($stored !== null) {
            return response($stored, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        $pdf = $this->buildPdf($submission);

        return $pdf->stream($filename);
    }

    /** Retorna o conteúdo binário do PDF para inclusão em ZIP / e-mail. */
    public function getSubmissionPdfContent(FormSubmission $submission): string
    {
        $stored = $this->readStoredPdf($submission);
        if ($stored !== null) {
            return $stored;
        }

        return $this->buildPdf($submission)->output();
    }

    /**
     * Persiste o PDF no storage e atualiza metadados na submission.
     */
    public function persistSubmissionPdf(FormSubmission $submission): string
    {
        $content = $this->buildPdf($submission)->output();
        $sha = hash('sha256', $content);
        $orgId = (int) ($submission->organization_id ?? $submission->clinic_id ?? 0);
        $path = sprintf(
            'org-%d/protocols/%s/protocolo-%s.pdf',
            $orgId,
            $submission->id,
            $submission->protocol_number ?? $submission->id
        );

        Storage::disk('minio_submissions')->put($path, $content);

        $submission->update([
            'pdf_disk_path' => $path,
            'pdf_sha256' => $sha,
            'pdf_generated_at' => now(),
        ]);

        return $content;
    }

    private function readStoredPdf(FormSubmission $submission): ?string
    {
        $path = $submission->pdf_disk_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        try {
            $disk = Storage::disk('minio_submissions');
            if (! $disk->exists($path)) {
                return null;
            }
            $bytes = $disk->get($path);

            return is_string($bytes) && $bytes !== '' ? $bytes : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
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
