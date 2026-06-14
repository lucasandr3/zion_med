<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ReleaseNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReleaseNote */
class ReleaseNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'title' => $this->title,
            'summary' => $this->summary,
            'items' => $this->items ?? [],
            'released_at' => $this->released_at?->format('Y-m-d'),
            'is_published' => (bool) $this->is_published,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
