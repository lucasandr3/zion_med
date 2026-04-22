<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProtocolDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = (new ProtocolResource($this->resource))->toArray($request);

        return array_merge($base, [
            'values' => $this->whenLoaded('values', function () {
                return $this->values->mapWithKeys(function ($v) {
                    $value = $v->value_json ?? $v->value_text;
                    return [$v->key => $value];
                });
            }),
            'values_keyed' => $this->whenLoaded('values', function () {
                return $this->values->mapWithKeys(function ($v) {
                    return [$v->key => [
                        'value_text' => $v->value_text,
                        'value_json' => $v->value_json,
                    ]];
                });
            }),
            'form_data' => $this->whenLoaded('values', function () {
                return $this->values->mapWithKeys(function ($v) {
                    $value = $v->value_json ?? $v->value_text;
                    return [$v->key => $value];
                });
            }),
            'template' => $this->whenLoaded('template', fn () => new TemplateResource($this->template)),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'id' => $event->id,
                'type' => $event->type,
                'type_label' => $event->type_label,
                'body' => $event->body,
                'created_at' => $event->created_at?->toIso8601String(),
                'meta' => $event->meta_json,
                'user' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                ] : null,
            ])->values()),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'field_key' => $attachment->field_key,
                'original_name' => $attachment->original_name,
                'mime' => $attachment->mime,
                'size' => $attachment->size,
                'url' => $attachment->url,
            ])->values()),
            'signatures' => $this->whenLoaded('signatures', fn () => $this->signatures->map(fn ($signature) => [
                'id' => $signature->id,
                'field_key' => $signature->field_key,
                'url' => $signature->url,
                'signed_name' => $signature->signed_name,
                'signed_ip' => $signature->signed_ip,
                'signed_user_agent' => $signature->signed_user_agent,
                'signed_hash' => $signature->signed_hash,
                'document_hash' => $signature->document_hash,
                'evidence_hash' => $signature->evidence_hash,
                'channel' => $signature->channel,
                'status' => $signature->status,
                'locale' => $signature->locale,
                'timezone' => $signature->timezone,
                'accepted_text_at' => $signature->accepted_text_at?->toIso8601String(),
                'signed_at' => $signature->signed_at?->toIso8601String(),
            ])->values()),
        ]);
    }
}
