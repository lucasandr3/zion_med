<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\GenerateSubmissionPdfJob;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DOC-02 — status observável da geração do PDF arquivado (sem migração).
 *
 * @return 'ready'|'partial'|'pending'|'failed'|null
 */
final class PdfGenerationStatus
{
    public static function resolve(FormSubmission $submission, bool $checkQueues = true): ?string
    {
        $hasPath = filled($submission->pdf_disk_path);
        $hasHash = filled($submission->pdf_sha256);

        if ($hasPath && $hasHash) {
            return 'ready';
        }

        if ($hasPath xor $hasHash) {
            return 'partial';
        }

        if ($checkQueues && self::hasFailedJob((int) $submission->id)) {
            return 'failed';
        }

        if ($checkQueues && self::hasQueuedJob((int) $submission->id)) {
            return 'pending';
        }

        if (filled($submission->patient_download_token)) {
            return 'pending';
        }

        return null;
    }

    private static function hasFailedJob(int $submissionId): bool
    {
        if (! Schema::hasTable('failed_jobs')) {
            return false;
        }

        return DB::table('failed_jobs')
            ->where(function ($q) use ($submissionId) {
                // Serialização PHP típica do job: public int $submissionId
                $q->where('payload', 'like', '%GenerateSubmissionPdfJob%')
                    ->where(function ($inner) use ($submissionId) {
                        $inner->where('payload', 'like', '%submissionId";i:'.$submissionId.';%')
                            ->orWhere('payload', 'like', '%"submissionId":'.$submissionId.'%')
                            ->orWhere('payload', 'like', '%"submissionId": '.$submissionId.'%');
                    });
            })
            ->exists();
    }

    private static function hasQueuedJob(int $submissionId): bool
    {
        if (! Schema::hasTable('jobs')) {
            return false;
        }

        return DB::table('jobs')
            ->where('payload', 'like', '%'.GenerateSubmissionPdfJob::class.'%')
            ->where(function ($q) use ($submissionId) {
                $q->where('payload', 'like', '%submissionId";i:'.$submissionId.';%')
                    ->orWhere('payload', 'like', '%"submissionId":'.$submissionId.'%')
                    ->orWhere('payload', 'like', '%"submissionId": '.$submissionId.'%');
            })
            ->exists();
    }
}
