<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'public_enabled' => $this->public_enabled,
            'public_require_person_link' => (bool) $this->public_require_person_link,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'fields' => $this->whenLoaded(
                'fields',
                fn () => FormFieldResource::collection($this->fields)
            ),
        ];
    }
}
