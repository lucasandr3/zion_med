<?php

namespace App\Services\Submissions;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\SubmissionAttachment;
use App\Models\SubmissionValue;
use Illuminate\Http\UploadedFile;

class SubmissionPersistenceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function writeFieldValues(FormSubmission $submission, FormTemplate $template, array $data): void
    {
        foreach ($template->fields as $field) {
            $key = $field->name_key;
            if (! array_key_exists($key, $data) && $field->type !== 'signature' && $field->type !== 'file') {
                continue;
            }
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                SubmissionValue::create([
                    'submission_id' => $submission->id,
                    'field_id' => $field->id,
                    'key' => $key,
                    'value_text' => null,
                    'value_json' => $value,
                ]);
            } else {
                SubmissionValue::create([
                    'submission_id' => $submission->id,
                    'field_id' => $field->id,
                    'key' => $key,
                    'value_text' => (string) $value,
                    'value_json' => null,
                ]);
            }
        }
    }

    public function applyDocumentHashes(
        FormSubmission $submission,
        FormTemplate $template,
        FormTemplateVersion $templateVersion,
    ): string {
        $submission->load('values');
        $valuesKeyed = $submission->values->keyBy('key')->map(fn ($v) => $v->value_json ?? $v->value_text)->all();
        $documentSnapshot = [
            'protocol_number' => $submission->protocol_number,
            'template_version_id' => $templateVersion->id,
            'template_name' => $template->name,
            'template_description' => $template->description,
            'fields_snapshot' => $templateVersion->fields_snapshot,
            'values' => $valuesKeyed,
            'submitted_at' => $submission->submitted_at->toIso8601String(),
        ];
        $documentSnapshotHash = hash('sha256', json_encode($documentSnapshot));
        $documentHash = hash('sha256', implode('|', [
            $submission->protocol_number,
            (string) $templateVersion->id,
            $documentSnapshotHash,
            $submission->submitted_at->toIso8601String(),
        ]));
        $submission->update([
            'document_hash' => $documentHash,
            'document_snapshot_hash' => $documentSnapshotHash,
        ]);

        return $documentHash;
    }

    /**
     * @param  array<string, UploadedFile>  $files
     */
    public function storeAttachments(int $organizationId, FormSubmission $submission, array $files): void
    {
        foreach ($files as $fieldKey => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store(
                'organizations/'.$organizationId.'/submissions/'.$submission->id,
                'minio_attachments'
            );
            SubmissionAttachment::create([
                'submission_id' => $submission->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'field_key' => $fieldKey,
            ]);
        }
    }
}
