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
            'template' => $this->whenLoaded('template', fn () => new TemplateResource($this->template)),
        ]);
    }
}
