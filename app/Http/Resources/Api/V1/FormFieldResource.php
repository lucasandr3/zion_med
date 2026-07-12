<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $options = $this->options_json['options'] ?? null;

        return [
            'id' => $this->id,
            'name_key' => $this->name_key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'options' => $options,
            'sort_order' => $this->sort_order,
            'visibility_rules' => $this->visibility_rules,
            'clinical_step_kind' => $this->clinical_step_kind,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
