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
            ],
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
