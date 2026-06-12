<?php

declare(strict_types=1);

namespace App\Support;

final class MailBrand
{
    /**
     * Variáveis padrão para templates de e-mail (identidade da plataforma).
     *
     * @return array<string, mixed>
     */
    public static function variables(): array
    {
        $name = (string) (config('mail.branding.product_name')
            ?: config('asaas.product_name')
            ?: config('app.name'));

        return [
            'brandName' => $name,
            'brandLogoUrl' => config('mail.branding.logo_url'),
            'brandPrimary' => (string) (config('mail.branding.primary_color') ?: '#1a3fae'),
            'brandSupportEmail' => config('mail.branding.support_email'),
            'signaturePhotoUrl' => config('mail.branding.signature_photo_url'),
            'senderName' => config('mail.branding.sender_name') ?: $name,
            'senderRole' => config('mail.branding.sender_role'),
            'senderEmail' => config('mail.branding.sender_email'),
            'whatsappNumber' => config('mail.branding.whatsapp_number'),
            'year' => now()->year,
            'manualEmail' => false,
            'actionLink' => null,
            'actionText' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function with(array $extra = []): array
    {
        return array_merge(self::variables(), $extra);
    }
}
