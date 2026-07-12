<?php

namespace App\Services;

use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class DocumentVerificationService
{
    public const MIN_CODE_LENGTH = 8;

    /**
     * Resolve um protocolo público a partir do código de verificação.
     * Aceita: hash completo (64 hex), prefixo do hash (≥8) ou número do protocolo.
     *
     * @return array{valid: bool, reason?: string, document?: array<string, mixed>}
     */
    public function verify(string $rawCode): array
    {
        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            throw new InvalidArgumentException('Informe o código de verificação.');
        }

        if (strlen($code) < self::MIN_CODE_LENGTH && ! $this->looksLikeProtocolNumber($code)) {
            return [
                'valid' => false,
                'reason' => 'Código muito curto. Use o código impresso no documento ou o hash completo.',
            ];
        }

        $matches = $this->findCandidates($code);

        if ($matches->isEmpty()) {
            return [
                'valid' => false,
                'reason' => 'Nenhum documento encontrado para este código.',
            ];
        }

        if ($matches->count() > 1) {
            return [
                'valid' => false,
                'reason' => 'Código ambíguo. Use o hash SHA-256 completo impresso no documento.',
            ];
        }

        /** @var FormSubmission $submission */
        $submission = $matches->first();
        $submission->loadMissing(['organization', 'template', 'signatures']);

        $documentHash = (string) ($submission->document_hash ?? '');
        $hasSignature = $submission->signatures->isNotEmpty();

        return [
            'valid' => $documentHash !== '',
            'reason' => $documentHash === ''
                ? 'Documento encontrado, mas sem hash de integridade registrado.'
                : null,
            'document' => [
                'protocol_number' => $submission->protocol_number,
                'verification_code' => $documentHash !== ''
                    ? strtoupper(substr($documentHash, 0, 8))
                    : null,
                'document_hash' => $documentHash !== '' ? $documentHash : null,
                'template_name' => $submission->template?->name,
                'clinic_name' => $submission->organization?->name,
                'submitted_at' => $submission->submitted_at?->toIso8601String()
                    ?? $submission->created_at?->toIso8601String(),
                'status' => $submission->status?->value ?? (string) $submission->status,
                'has_signature' => $hasSignature,
                'signed_at' => $hasSignature
                    ? $submission->signatures->sortByDesc('signed_at')->first()?->signed_at?->toIso8601String()
                    : null,
                'evidence' => $this->buildPublicEvidence($submission),
            ],
        ];
    }

    /**
     * Evidências públicas de defensabilidade clínica (sem PII).
     *
     * @return array<string, mixed>|null
     */
    private function buildPublicEvidence(FormSubmission $submission): ?array
    {
        $snapshot = is_array($submission->document_snapshot) ? $submission->document_snapshot : [];
        $clinical = is_array($snapshot['clinical'] ?? null) ? $snapshot['clinical'] : [];
        $actors = is_array($clinical['actors'] ?? null) ? $clinical['actors'] : [];
        $documentKind = $snapshot['document_kind'] ?? $clinical['document_kind'] ?? null;

        $hasClinicalMeta = $clinical !== [] || $documentKind !== null;
        if (! $hasClinicalMeta && $submission->approved_at === null && $submission->retention_anonymized_at === null) {
            return null;
        }

        return [
            'document_kind' => $documentKind,
            'template_version' => $snapshot['template_version'] ?? null,
            'comprehension_ack' => (bool) ($clinical['comprehension_ack'] ?? false),
            'comprehension_ack_at' => $clinical['comprehension_ack_at'] ?? null,
            'term_scrolled_at' => $clinical['term_scrolled_at'] ?? null,
            'privacy_ack' => (bool) ($clinical['privacy_ack'] ?? false),
            'comprehension_quiz_passed' => (bool) ($clinical['comprehension_quiz_passed'] ?? false),
            'assisted_mode' => (bool) ($clinical['assisted_mode'] ?? false),
            'professional_explained' => (bool) ($clinical['professional_explained_at_submit'] ?? false),
            'has_guardian' => trim((string) ($actors['guardian_name'] ?? '')) !== '',
            'has_witness' => trim((string) ($actors['witness_name'] ?? '')) !== '',
            'clinical_steps_completed' => is_array($clinical['clinical_steps_completed'] ?? null)
                ? count($clinical['clinical_steps_completed'])
                : 0,
            'approved_at' => $submission->approved_at?->toIso8601String(),
            'revoked_at' => $submission->revoked_at?->toIso8601String(),
            'retention_anonymized_at' => $submission->retention_anonymized_at?->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, FormSubmission>
     */
    private function findCandidates(string $code): Collection
    {
        $query = FormSubmission::withoutGlobalScopes()->with(['organization', 'template', 'signatures']);

        // Hash completo (SHA-256 hex)
        if (preg_match('/^[A-F0-9]{64}$/', $code) === 1) {
            return $query->whereRaw('LOWER(document_hash) = ?', [strtolower($code)])->limit(2)->get();
        }

        // Prefixo do hash (código impresso)
        if (preg_match('/^[A-F0-9]{8,63}$/', $code) === 1) {
            $prefix = strtolower($code);

            return $query
                ->whereNotNull('document_hash')
                ->where('document_hash', 'like', $prefix.'%')
                ->limit(2)
                ->get();
        }

        // Número do protocolo (ex.: ZM-AAA-001)
        return $query
            ->where(function ($q) use ($code) {
                $q->where('protocol_number', $code)
                    ->orWhereRaw('UPPER(protocol_number) = ?', [$code]);
            })
            ->limit(2)
            ->get();
    }

    private function looksLikeProtocolNumber(string $code): bool
    {
        return preg_match('/^[A-Z0-9][A-Z0-9\-]{2,31}$/', $code) === 1;
    }
}
