<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => (string) $this->role,
            'role_label' => $this->resource->resolveRoleLabel(),
            'permissions' => $this->resource->effectivePermissions(),
            'active' => $this->active,
            'can_switch_clinic' => $this->can_switch_clinic ?? false,
            'ui_theme' => $this->ui_theme,
            'ui_dark_mode' => $this->ui_dark_mode,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
