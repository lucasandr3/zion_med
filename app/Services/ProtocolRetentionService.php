<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\Organization;
use App\Models\SubmissionAttachment;
use App\Models\SubmissionSignature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProtocolRetentionService
{
    private const RETAINED_PLACEHOLDER = '[dados retidos por política LGPD]';

    public function __construct(
        private AuditService $auditService,
    ) {}

    /**
     * @return array{
     *   enabled: bool,
     *   protocol_retention_years: int|null,
     *   protocol_retention_mode: string,
     *   cutoff_date: string|null,
     *   eligible_count: int,
     *   already_anonymized_count: int
     * }
     */
    public function preview(Organization $organization): array
    {
        $years = (int) ($organization->protocol_retention_years ?? 0);
        if ($years < 1) {
            return [
                'enabled' => false,
                'protocol_retention_years' => null,
                'protocol_retention_mode' => $organization->protocol_retention_mode ?? 'anonymize',
                'cutoff_date' => null,
                'eligible_count' => 0,
                'already_anonymized_count' => 0,
            ];
        }

        $cutoff = now()->subYears($years);

        return [
            'enabled' => true,
            'protocol_retention_years' => $years,
            'protocol_retention_mode' => $organization->protocol_retention_mode ?? 'anonymize',
            'cutoff_date' => $cutoff->toIso8601String(),
            'eligible_count' => $this->eligibleQuery($organization, $cutoff)->count(),
            'already_anonymized_count' => FormSubmission::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->whereNotNull('retention_anonymized_at')
                ->count(),
        ];
    }

    public function applyForOrganization(Organization $organization, bool $dryRun = false): int
    {
        $years = (int) ($organization->protocol_retention_years ?? 0);
        if ($years < 1) {
            return 0;
        }

        $cutoff = now()->subYears($years);
        $mode = $organization->protocol_retention_mode === 'delete' ? 'delete' : 'anonymize';
        $processed = 0;

        $this->eligibleQuery($organization, $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($submissions) use (&$processed, $mode, $dryRun, $organization): void {
                foreach ($submissions as $submission) {
                    if ($dryRun) {
                        $processed++;

                        continue;
                    }

                    if ($mode === 'delete') {
                        $this->deleteSubmission($submission);
                    } else {
                        $this->anonymizeSubmission($submission);
                    }
                    $processed++;
                }
            });

        if (! $dryRun && $processed > 0) {
            $this->auditService->log(
                'protocol_retention_applied',
                'organization',
                $organization->id,
                [
                    'mode' => $mode,
                    'processed' => $processed,
                    'years' => $years,
                    'cutoff' => $cutoff->toIso8601String(),
                ],
                $organization->id,
            );
        }

        return $processed;
    }

    private function eligibleQuery(Organization $organization, \DateTimeInterface $cutoff): Builder
    {
        return FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNull('retention_anonymized_at')
            ->whereIn('status', [
                SubmissionStatus::Approved,
                SubmissionStatus::Rejected,
                SubmissionStatus::Revoked,
            ])
            ->where(function (Builder $q) use ($cutoff): void {
                $q->where(function (Builder $w) use ($cutoff): void {
                    $w->whereNotNull('approved_at')->where('approved_at', '<', $cutoff);
                })->orWhere(function (Builder $w) use ($cutoff): void {
                    $w->whereNull('approved_at')->where('submitted_at', '<', $cutoff);
                });
            });
    }

    private function anonymizeSubmission(FormSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $submission->load(['values', 'attachments', 'signatures']);

            foreach ($submission->attachments as $attachment) {
                $this->deleteAttachmentFile($attachment);
                $attachment->delete();
            }

            foreach ($submission->signatures as $signature) {
                $this->deleteSignatureFile($signature);
                $signature->update([
                    'image_path' => null,
                    'signed_name' => self::RETAINED_PLACEHOLDER,
                    'signed_ip' => null,
                    'signed_user_agent' => null,
                ]);
            }

            foreach ($submission->values as $value) {
                $value->update([
                    'value_text' => self::RETAINED_PLACEHOLDER,
                    'value_json' => null,
                ]);
            }

            $snapshot = is_array($submission->document_snapshot)
                ? $this->scrubSnapshot($submission->document_snapshot)
                : null;

            $submission->update([
                'submitter_name' => self::RETAINED_PLACEHOLDER,
                'submitter_email' => null,
                'review_comment' => null,
                'revoke_reason' => $submission->revoke_reason ? self::RETAINED_PLACEHOLDER : null,
                'document_snapshot' => $snapshot,
                'retention_anonymized_at' => now(),
            ]);
        });
    }

    private function deleteSubmission(FormSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            $submission->load(['attachments', 'signatures']);

            foreach ($submission->attachments as $attachment) {
                $this->deleteAttachmentFile($attachment);
                $attachment->delete();
            }

            foreach ($submission->signatures as $signature) {
                $this->deleteSignatureFile($signature);
                $signature->delete();
            }

            $submission->values()->delete();
            $submission->events()->delete();
            $submission->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function scrubSnapshot(array $snapshot): array
    {
        if (isset($snapshot['values']) && is_array($snapshot['values'])) {
            foreach ($snapshot['values'] as $key => $value) {
                $snapshot['values'][$key] = self::RETAINED_PLACEHOLDER;
            }
        }

        if (isset($snapshot['clinical']) && is_array($snapshot['clinical'])) {
            if (isset($snapshot['clinical']['actors']) && is_array($snapshot['clinical']['actors'])) {
                foreach (['guardian_name', 'guardian_relation', 'witness_name'] as $field) {
                    if (array_key_exists($field, $snapshot['clinical']['actors'])) {
                        $snapshot['clinical']['actors'][$field] = self::RETAINED_PLACEHOLDER;
                    }
                }
            }
        }

        $snapshot['retention_notice'] = 'Conteúdo identificável anonimizado em '.now()->toIso8601String();

        return $snapshot;
    }

    private function deleteAttachmentFile(SubmissionAttachment $attachment): void
    {
        if (! $attachment->file_path) {
            return;
        }
        foreach (['minio_attachments', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($attachment->file_path)) {
                Storage::disk($disk)->delete($attachment->file_path);
            }
        }
    }

    private function deleteSignatureFile(SubmissionSignature $signature): void
    {
        if (! $signature->image_path) {
            return;
        }
        foreach (['minio_submissions', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($signature->image_path)) {
                Storage::disk($disk)->delete($signature->image_path);
            }
        }
    }
}
