<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_path ? $this->logo_url : null,
            'cover_image_url' => $this->cover_image_path ? $this->cover_image_url : null,
            'theme' => $this->theme,
            'dark_mode' => $this->dark_mode ?? false,
            'plan_key' => $this->plan_key,
            'subscription_status' => $this->subscription_status,
            'billing_status' => $this->billing_status,
            'notification_email' => $this->notification_email,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'address' => $this->address,
            // Campos adicionais usados na tela de configurações da clínica (API Angular)
            'billing_name' => $this->billing_name,
            'billing_email' => $this->billing_email,
            'billing_document' => $this->billing_document,
            'business_hours' => $this->business_hours,
            'whatsapp_notifications_enabled' => $this->whatsapp_notifications_enabled,
            'whatsapp_notify_cobranca' => $this->whatsapp_notify_cobranca,
            'whatsapp_notify_faturas_boleto' => $this->whatsapp_notify_faturas_boleto,
            'whatsapp_notify_avisos' => $this->whatsapp_notify_avisos,
            'short_description' => $this->short_description,
            'specialties' => $this->specialties,
            'founded_year' => $this->founded_year,
            'meta_description' => $this->meta_description,
            'maps_url' => $this->maps_url,
            'public_theme' => $this->public_theme,
            'cover_color' => $this->cover_color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
