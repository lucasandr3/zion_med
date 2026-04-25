<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'niche' => $this->niche ?: 'estetica',
            'logo_url' => $this->logo_path ? $this->logo_url : null,
            'company_logo_url' => null,
            'professional_photo_url' => $this->professional_photo_path ? $this->professional_photo_url : null,
            'cover_image_url' => $this->cover_image_path ? $this->cover_image_url : null,
            'cover_mode' => $this->cover_mode ?? 'banner',
            'link_bio_model' => (int) ($this->link_bio_model ?? 1),
            'link_bio_extra' => $this->link_bio_extra,
            'theme' => $this->theme,
            'dark_mode' => $this->dark_mode ?? false,
            'plan_key' => $this->plan_key,
            'plan_limits' => $this->planLimitsForApi(),
            'subscription_status' => $this->subscription_status,
            'billing_status' => $this->billing_status,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'notification_email' => $this->notification_email,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'address' => $this->address,
            'address_data' => $this->addressData ? [
                'cep' => $this->addressData->cep,
                'logradouro' => $this->addressData->logradouro,
                'numero' => $this->addressData->numero,
                'complemento' => $this->addressData->complemento,
                'bairro' => $this->addressData->bairro,
                'cidade' => $this->addressData->cidade,
                'uf' => $this->addressData->uf,
            ] : null,
            'billing_name' => $this->billing_name,
            'billing_email' => $this->billing_email,
            'billing_document' => $this->billing_document,
            'business_hours' => $this->business_hours,
            'whatsapp_notifications_enabled' => $this->whatsapp_notifications_enabled,
            'whatsapp_notify_cobranca' => $this->whatsapp_notify_cobranca,
            'whatsapp_notify_faturas_boleto' => $this->whatsapp_notify_faturas_boleto,
            'whatsapp_notify_avisos' => $this->whatsapp_notify_avisos,
            'signing_security_level' => $this->signing_security_level ?? 'basic',
            'data_retention_years' => $this->data_retention_years !== null ? (int) $this->data_retention_years : null,
            'short_description' => $this->short_description,
            'specialties' => $this->specialties,
            'founded_year' => $this->founded_year,
            'meta_description' => $this->meta_description,
            'maps_url' => $this->maps_url,
            'public_theme' => $this->public_theme,
            'accent_hex' => $this->accent_hex,
            'cover_color' => $this->cover_color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
