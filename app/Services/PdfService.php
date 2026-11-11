<?php

namespace App\Services;

use App\Exceptions\PdfIntegrityException;
use App\Models\FormSubmission;
use App\Support\EsteticaStaffFieldRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function streamSubmissionPdf(FormSubmission $submission): \Illuminate\Http\Response
    {
        $content = $this->resolvePdfContent($submission);
        $filename = 'protocolo-' . ($submission->protocol_number ?? $submission->id) . '.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /** Retorna o conteúdo binário do PDF para inclusão em ZIP / e-mail. */
    public function getSubmissionPdfContent(FormSubmission $submission): string
    {
        return $this->resolvePdfContent($submission);
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

    /**
     * Serve o PDF persistido (com checagem de hash). Só regenera se nunca houve arquivo/hash.
     * Se o arquivo foi adulterado, lança PdfIntegrityException.
     */
    private function resolvePdfContent(FormSubmission $submission): string
    {
        $stored = $this->readStoredPdf($submission);
        if ($stored !== null) {
            return $stored;
        }

        $hasIntegrityAnchor = is_string($submission->pdf_sha256) && $submission->pdf_sha256 !== '';
        $hasPath = is_string($submission->pdf_disk_path) && $submission->pdf_disk_path !== '';

        // Havia PDF registrado, mas o arquivo sumiu: regenera e regrava (recuperação operacional).
        if ($hasPath || $hasIntegrityAnchor) {
            report(new \RuntimeException(
                'PDF ausente no storage; regenerando submission='.$submission->id
            ));

            return $this->persistSubmissionPdf($submission);
        }

        return $this->buildPdf($submission)->output();
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
            if (! is_string($bytes) || $bytes === '') {
                return null;
            }

            $expected = $submission->pdf_sha256;
            if (is_string($expected) && $expected !== '') {
                $actual = hash('sha256', $bytes);
                if (! hash_equals($expected, $actual)) {
                    throw new PdfIntegrityException(
                        'PDF armazenado diverge do pdf_sha256 registrado (submission='.$submission->id.').'
                    );
                }
            }

            return $bytes;
        } catch (PdfIntegrityException $e) {
            throw $e;
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
