<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;

class PersonConsentService
{
    /**
     * @return array{
     *   status: string,
     *   label: string,
     *   active_protocol_id: int|null,
     *   template_id: int|null,
     *   active_protocol_number: string|null,
     *   active_submitted_at: string|null,
     *   valid_until: string|null,
     *   consents_count: int
     * }
     */
    public function resolveSummary(Person $person, ?int $excludeSubmissionId = null): array
    {
        $consentProtocols = $this->consentSubmissions($person, $excludeSubmissionId);

        $activeConsent = $consentProtocols->first(fn ($s) => $s->isConsentCurrentlyValid());
        $pendingConsent = $consentProtocols->first(fn ($s) => $s->status === SubmissionStatus::Pending);
        $expiredConsent = $consentProtocols->first(fn ($s) => $s->isConsentExpired());
        $revokedConsent = $consentProtocols->first(fn ($s) => $s->status === SubmissionStatus::Revoked);

        $consentStatus = 'none';
        if ($activeConsent) {
            $consentStatus = 'valid';
        } elseif ($pendingConsent) {
            $consentStatus = 'pending';
        } elseif ($expiredConsent) {
            $consentStatus = 'expired';
        } elseif ($revokedConsent) {
            $consentStatus = 'revoked';
        } elseif ($consentProtocols->isNotEmpty()) {
            $consentStatus = 'inactive';
        }

        $summaryProtocol = $activeConsent ?? $pendingConsent ?? $expiredConsent;

        return [
            'status' => $consentStatus,
            'label' => match ($consentStatus) {
                'valid' => 'Consentimento válido',
                'pending' => 'Consentimento pendente de revisão',
                'expired' => 'Consentimento vencido',
                'revoked' => 'Consentimento revogado',
                'inactive' => 'Sem consentimento vigente',
                default => 'Nenhum consentimento registrado',
            },
            'active_protocol_id' => $summaryProtocol?->id,
            'template_id' => $summaryProtocol?->template_id,
            'active_protocol_number' => $summaryProtocol?->protocol_number,
            'active_submitted_at' => $summaryProtocol?->submitted_at?->toIso8601String(),
            'valid_until' => $summaryProtocol?->consent_valid_until?->toIso8601String(),
            'consents_count' => $consentProtocols->count(),
        ];
    }

    public function canScheduleProcedure(Person $person, ?int $excludeSubmissionId = null): bool
    {
        return $this->resolveSummary($person, $excludeSubmissionId)['status'] === 'valid';
    }

    /**
     * @param  array{status: string, label?: string, active_protocol_number?: string|null}  $summary
     */
    public function schedulingBlockMessage(array $summary): string
    {
        return match ($summary['status'] ?? '') {
            'pending' => 'Consentimento pendente de revisão. Aguarde a aprovação clínica antes de agendar o procedimento.',
            'expired' => 'Consentimento vencido. Solicite um novo termo antes de agendar o procedimento.',
            'revoked' => 'Consentimento revogado. Não é possível agendar o procedimento até regularizar a situação.',
            'inactive' => 'Sem consentimento vigente. Registre e aprove um termo antes de agendar o procedimento.',
            'none' => 'Nenhum consentimento registrado para esta pessoa. Conclua o termo antes de agendar o procedimento.',
            default => 'Consentimento não válido para agendamento de procedimento.',
        };
    }

    /**
     * @return Collection<int, FormSubmission>
     */
    private function consentSubmissions(Person $person, ?int $excludeSubmissionId = null): Collection
    {
        $query = $person->submissions()
            ->with('template')
            ->whereHas('template', function ($q) {
                $q->where('document_kind', 'consentimento')
                    ->orWhere('category', 'consentimento');
            })
            ->latest('submitted_at')
            ->limit(20);

        if ($excludeSubmissionId !== null) {
            $query->where('id', '!=', $excludeSubmissionId);
        }

        return $query->get();
    }
}
