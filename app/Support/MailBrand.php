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
            'brandPrimary' => (string) config('mail.branding.primary_color', '#1e40af'),
            'brandSupportEmail' => config('mail.branding.support_email'),
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
