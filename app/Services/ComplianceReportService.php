<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ComplianceReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(int $organizationId, bool $consentOnly = false, int $recentLimit = 8): array
    {
        $base = $this->baseQuery($organizationId, $consentOnly);

        $byStatus = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v, $k) => (int) $v)
            ->all();

        $pending = (int) ($byStatus[SubmissionStatus::Pending->value] ?? 0);
        $approved = (int) ($byStatus[SubmissionStatus::Approved->value] ?? 0);
        $rejected = (int) ($byStatus[SubmissionStatus::Rejected->value] ?? 0);
        $revoked = (int) ($byStatus[SubmissionStatus::Revoked->value] ?? 0);
        $total = array_sum($byStatus);

        $consentExpired = (clone $base)
            ->where('status', SubmissionStatus::Approved)
            ->whereNotNull('consent_valid_until')
            ->where('consent_valid_until', '<', now())
            ->count();

        $withoutSnapshot = (clone $base)
            ->where(function (Builder $q): void {
                $q->whereNull('document_snapshot_hash')
                    ->orWhereNull('document_snapshot');
            })
            ->count();

        $retentionAnonymized = (clone $base)
            ->whereNotNull('retention_anonymized_at')
            ->count();

        $pendingToday = (clone $base)
            ->where('status', SubmissionStatus::Pending)
            ->whereDate('created_at', today())
            ->count();

        $expiringNext30Days = (clone $base)
            ->where('status', SubmissionStatus::Approved)
            ->whereNotNull('consent_valid_until')
            ->whereBetween('consent_valid_until', [now(), now()->addDays(30)])
            ->count();

        $revocationDenominator = $approved + $rejected + $revoked;
        $revocationRate = $revocationDenominator > 0
            ? round(($revoked / $revocationDenominator) * 100, 1)
            : 0.0;

        return [
            'generated_at' => now()->toIso8601String(),
            'organization_id' => $organizationId,
            'scope' => $consentOnly ? 'consent' : 'all',
            'summary' => [
                'total_protocols' => $total,
                'pending' => $pending,
                'consent_expired' => (int) $consentExpired,
                'without_snapshot' => (int) $withoutSnapshot,
                'revoked' => $revoked,
                'retention_anonymized' => (int) $retentionAnonymized,
                'revocation_rate_percent' => $revocationRate,
                'revocation_rate_denominator' => 'approved_rejected_revoked',
            ],
            'by_status' => [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'revoked' => $revoked,
            ],
            'highlights' => [
                'pending_today' => (int) $pendingToday,
                'expiring_next_30_days' => (int) $expiringNext30Days,
            ],
            'recent_issues' => $this->recentIssues($organizationId, $consentOnly, $recentLimit),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentIssues(int $organizationId, bool $consentOnly, int $limit): array
    {
        $issues = [];

        $expired = $this->baseQuery($organizationId, $consentOnly)
            ->with(['template:id,name', 'person:id,name'])
            ->where('status', SubmissionStatus::Approved)
            ->whereNotNull('consent_valid_until')
            ->where('consent_valid_until', '<', now())
            ->latest('consent_valid_until')
            ->limit($limit)
            ->get();

        foreach ($expired as $submission) {
            $issues[] = $this->mapIssue($submission, 'consent_expired');
        }

        if (count($issues) >= $limit) {
            return array_slice($issues, 0, $limit);
        }

        $remaining = $limit - count($issues);
        $missingSnapshot = $this->baseQuery($organizationId, $consentOnly)
            ->with(['template:id,name', 'person:id,name'])
            ->where(function (Builder $q): void {
                $q->whereNull('document_snapshot_hash')
                    ->orWhereNull('document_snapshot');
            })
            ->latest('created_at')
            ->limit($remaining)
            ->get();

        foreach ($missingSnapshot as $submission) {
            $issues[] = $this->mapIssue($submission, 'without_snapshot');
        }

        if (count($issues) >= $limit) {
            return array_slice($issues, 0, $limit);
        }

        $remaining = $limit - count($issues);
        $pending = $this->baseQuery($organizationId, $consentOnly)
            ->with(['template:id,name', 'person:id,name'])
            ->where('status', SubmissionStatus::Pending)
            ->oldest('created_at')
            ->limit($remaining)
            ->get();

        foreach ($pending as $submission) {
            $issues[] = $this->mapIssue($submission, 'pending_review');
        }

        return $issues;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapIssue(FormSubmission $submission, string $issue): array
    {
        return [
            'id' => (int) $submission->id,
            'protocol_number' => $submission->protocol_number,
            'issue' => $issue,
            'person_name' => $submission->person?->name ?: ($submission->submitter_name ?: 'Anônimo'),
            'template_name' => $submission->template?->name ?: 'Modelo removido',
            'status' => $submission->status?->value ?? (string) $submission->status,
            'consent_valid_until' => $submission->consent_valid_until instanceof Carbon
                ? $submission->consent_valid_until->toIso8601String()
                : null,
            'submitted_at' => $submission->submitted_at?->toIso8601String()
                ?? $submission->created_at?->toIso8601String(),
        ];
    }

    private function baseQuery(int $organizationId, bool $consentOnly): Builder
    {
        $query = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $organizationId);

        if ($consentOnly) {
            $query->whereHas('template', function (Builder $templateQuery): void {
                $templateQuery
                    ->where('document_kind', 'consentimento')
                    ->orWhere('category', 'consentimento');
            });
        }

        return $query;
    }
}
