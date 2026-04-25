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
            'phone_alt' => $this->phone_alt,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'sex' => $this->sex,
            'cpf' => $this->cpf,
            'rg' => $this->rg,
            'marital_status' => $this->marital_status,
            'profession' => $this->profession,
            'referred_by' => $this->referred_by,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'cep' => $this->cep,
            'lead_source_instagram' => (bool) $this->lead_source_instagram,
            'lead_source_google' => (bool) $this->lead_source_google,
            'lead_source_facebook' => (bool) $this->lead_source_facebook,
            'lead_source_indicacao_amigo' => (bool) $this->lead_source_indicacao_amigo,
            'lead_source_indicacao_medica' => (bool) $this->lead_source_indicacao_medica,
            'lead_source_plano_saude' => (bool) $this->lead_source_plano_saude,
            'lead_source_outro' => $this->lead_source_outro,
            'has_health_plan' => $this->has_health_plan,
            'health_plan_operator' => $this->health_plan_operator,
            'health_plan_card_number' => $this->health_plan_card_number,
            'lgpd_accept_comms' => (bool) $this->lgpd_accept_comms,
            'lgpd_accept_reminders' => (bool) $this->lgpd_accept_reminders,
            'notes' => $this->notes,
            'status' => $this->status,
            'protocols_count' => isset($this->resource->submissions_count) ? (int) $this->resource->submissions_count : null,
            'last_protocol_at' => $lastProtocol,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
