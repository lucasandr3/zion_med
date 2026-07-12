<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProtocolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'protocol_number' => $this->protocol_number,
            'document_hash' => $this->document_hash,
            'document_snapshot_hash' => $this->document_snapshot_hash,
            'template_version_id' => $this->template_version_id,
            'status' => $this->status->value,
            'template_id' => $this->template_id,
            'template_name' => $this->whenLoaded('template', fn () => $this->template->name),
            'person_id' => $this->person_id,
            'person' => $this->whenLoaded('person', fn () => [
                'id' => $this->person->id,
                'code' => $this->person->code,
                'name' => $this->person->name,
            ]),
            'submitter_name' => $this->submitter_name,
            'submitter_email' => $this->submitter_email,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'consent_valid_until' => $this->consent_valid_until?->toIso8601String(),
            'consent_expired' => $this->isConsentExpired(),
            'review_comment' => $this->review_comment,
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'retention_anonymized_at' => $this->retention_anonymized_at?->toIso8601String(),
            'revoke_reason' => $this->revoke_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
