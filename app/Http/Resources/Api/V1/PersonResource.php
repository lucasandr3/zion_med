<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastProtocol = null;
        if (isset($this->resource->submissions_max_submitted_at) && $this->resource->submissions_max_submitted_at !== null) {
            $lastProtocol = \Illuminate\Support\Carbon::parse($this->resource->submissions_max_submitted_at)->toIso8601String();
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'cpf' => $this->cpf,
            'notes' => $this->notes,
            'status' => $this->status,
            'protocols_count' => isset($this->resource->submissions_count) ? (int) $this->resource->submissions_count : null,
            'last_protocol_at' => $lastProtocol,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
