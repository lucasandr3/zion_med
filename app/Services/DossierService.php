<?php

namespace App\Services;

use App\Models\FormSubmission;
use ZipArchive;

class DossierService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Monta o JSON de evidências do protocolo para exportação/auditoria.
     */
    public function buildEvidenceJson(FormSubmission $submission): array
    {
        $submission->load(['template', 'templateVersion', 'values', 'signatures', 'events', 'organization']);

        $signaturesEvidence = $submission->signatures->map(fn ($sig) => [
            'field_key' => $sig->field_key,
            'signed_name' => $sig->signed_name,
            'signed_at' => $sig->signed_at?->toIso8601String(),
            'signed_ip_masked' => $sig->signed_ip ? $this->maskIp($sig->signed_ip) : null,
            'signed_user_agent' => $sig->signed_user_agent,
            'signed_hash' => $sig->signed_hash,
            'evidence_hash' => $sig->evidence_hash,
            'document_hash' => $sig->document_hash,
            'channel' => $sig->channel,
            'status' => $sig->status,
            'locale' => $sig->locale,
            'timezone' => $sig->timezone,
            'accepted_text_at' => $sig->accepted_text_at?->toIso8601String(),
        ])->all();

        $timeline = $submission->events->map(fn ($e) => [
            'type' => $e->type,
            'created_at' => $e->created_at->toIso8601String(),
            'meta' => $e->meta_json,
        ])->all();

        return [
            'protocol_number' => $submission->protocol_number,
            'submission_id' => $submission->id,
            'template_id' => $submission->template_id,
            'template_version_id' => $submission->template_version_id,
            'template_name' => $submission->template?->name,
            'document_hash' => $submission->document_hash,
            'document_snapshot_hash' => $submission->document_snapshot_hash,
            'signing_channel' => $submission->signing_channel,
            'signing_status' => $submission->signing_status,
            'locale' => $submission->locale,
            'timezone' => $submission->timezone,
            'submitter_name' => $submission->submitter_name,
            'submitter_email' => $submission->submitter_email,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'accepted_text_at' => $submission->accepted_text_at?->toIso8601String(),
            'signatures' => $signaturesEvidence,
            'timeline' => $timeline,
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Gera ZIP contendo PDF do protocolo + JSON de evidências. Retorna path do arquivo temporário.
     */
    public function createDossierZip(FormSubmission $submission): string
    {
        $pdfContent = $this->pdfService->getSubmissionPdfContent($submission);
        $evidence = $this->buildEvidenceJson($submission);
        $protocolNumber = $submission->protocol_number ?? (string) $submission->id;
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $protocolNumber);

        $tempZip = tempnam(sys_get_temp_dir(), 'dossier_');
        $zip = new ZipArchive;
        if ($zip->open($tempZip, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP.');
        }
        $zip->addFromString('protocolo-' . $safeName . '.pdf', $pdfContent);
        $zip->addFromString('evidencias-' . $safeName . '.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return $tempZip;
    }

    /**
     * Retorna path do ZIP e nome do arquivo para download.
     */
    public function streamDossierZip(FormSubmission $submission): array
    {
        $path = $this->createDossierZip($submission);
        $filename = 'dossie-' . ($submission->protocol_number ?? $submission->id) . '.zip';
        return ['path' => $path, 'filename' => $filename];
    }

    private function maskIp(?string $ip): string
    {
        if (! $ip) {
            return '';
        }
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            $last = array_pop($parts);
            return implode(':', $parts) . '::****';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '***';
            return implode('.', $parts);
        }
        return '***';
    }
}
