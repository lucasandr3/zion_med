<?php

use App\Models\PlatformSetting;
use App\Services\ResendConfigService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $setIfMissing = function (string $key, mixed $value): void {
            $current = PlatformSetting::get($key);
            if ($current !== null && $current !== '') {
                return;
            }
            PlatformSetting::set($key, $value);
        };

        $mailer = env('MAIL_MAILER');
        if (is_string($mailer) && in_array($mailer, ['resend', 'log'], true)) {
            $setIfMissing(ResendConfigService::KEY_MAILER, $mailer);
        }

        $resendKey = env('RESEND_API_KEY');
        if (is_string($resendKey) && $resendKey !== '' && ! PlatformSetting::get(ResendConfigService::KEY_API_KEY)) {
            PlatformSetting::set(ResendConfigService::KEY_API_KEY, Crypt::encryptString($resendKey));
        }

        $fromAddress = env('MAIL_FROM_ADDRESS');
        if (is_string($fromAddress) && $fromAddress !== '') {
            $setIfMissing(ResendConfigService::KEY_FROM_ADDRESS, $fromAddress);
        }

        $fromName = env('MAIL_FROM_NAME');
        if (is_string($fromName) && $fromName !== '') {
            $setIfMissing(ResendConfigService::KEY_FROM_NAME, $fromName);
        }

        $supportEmail = env('MAIL_SUPPORT_EMAIL');
        if (is_string($supportEmail) && $supportEmail !== '') {
            $setIfMissing(ResendConfigService::KEY_SUPPORT_EMAIL, $supportEmail);
        }

        $logoUrl = env('MAIL_LOGO_URL');
        if (is_string($logoUrl) && $logoUrl !== '') {
            $setIfMissing(ResendConfigService::KEY_LOGO_URL, $logoUrl);
        }

        $primaryColor = env('MAIL_PRIMARY_COLOR');
        if (is_string($primaryColor) && $primaryColor !== '') {
            $setIfMissing(ResendConfigService::KEY_PRIMARY_COLOR, $primaryColor);
        }

        $productName = env('MAIL_PRODUCT_NAME');
        if (is_string($productName) && $productName !== '') {
            $setIfMissing(ResendConfigService::KEY_PRODUCT_NAME, $productName);
        }
    }

    public function down(): void
    {
        // Configurações permanecem no banco; não revertemos dados de integração.
    }
};
