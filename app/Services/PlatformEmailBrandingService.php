<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PlatformEmailBrandingService
{
    private const WHATSAPP_PREFILL_MESSAGE = 'Olá, tudo bem? Vim pelo e-mail.';

    private const DEFAULT_WHATSAPP_DIGITS = '5534996460818';

    public function __construct(
        private readonly ResendConfigService $resendConfig,
        private readonly MinioConfigService $minioConfig,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function baseTemplateData(): array
    {
        $productName = $this->resendConfig->getProductName() ?: $this->resendConfig->getFromName();
        $senderName = $this->resendConfig->getSenderName() ?: $this->resendConfig->getFromName();
        $senderEmail = $this->resendConfig->getSupportEmail() ?: $this->resendConfig->getFromAddress();

        return [
            'brandName' => $productName,
            'brandLogoUrl' => $this->getEffectiveLogoUrl(),
            'brandPrimary' => $this->resendConfig->getPrimaryColor(),
            'brandSupportEmail' => $this->resendConfig->getSupportEmail(),
            'signaturePhotoUrl' => $this->getSignaturePhotoUrl(),
            'senderName' => $senderName,
            'senderRole' => $this->resendConfig->getSenderRole(),
            'senderEmail' => $senderEmail,
            'whatsappNumber' => $this->normalizeWhatsappNumber($this->resendConfig->getWhatsappNumber()),
            'whatsappUrl' => $this->getWhatsappContactUrl(),
            'year' => now()->year,
            'manualEmail' => false,
            'actionLink' => null,
            'actionText' => null,
        ];
    }

    public function getEffectiveLogoUrl(?int $minutes = 60): ?string
    {
        $path = $this->resendConfig->getLogoPath();
        if (is_string($path) && $path !== '') {
            return $this->resolveAssetUrl($path, $minutes);
        }

        return $this->resendConfig->getLogoUrl();
    }

    public function getSignaturePhotoUrl(?int $minutes = 60): ?string
    {
        $path = $this->resendConfig->getSignaturePhotoPath();

        return is_string($path) && $path !== ''
            ? $this->resolveAssetUrl($path, $minutes)
            : null;
    }

    /**
     * @return array{path: string, url: string|null}
     */
    public function uploadLogo(UploadedFile $file): array
    {
        return $this->uploadAsset('logo', $file, ResendConfigService::KEY_LOGO_PATH);
    }

    /**
     * @return array{path: string, url: string|null}
     */
    public function uploadSignaturePhoto(UploadedFile $file): array
    {
        return $this->uploadAsset('signature', $file, ResendConfigService::KEY_SIGNATURE_PHOTO_PATH);
    }

    public function formatBodyHtml(string $plain): string
    {
        $lines = preg_split("/\r\n|\r|\n/", trim($plain)) ?: [];
        $chunks = [];
        $buffer = [];

        foreach ($lines as $line) {
            if (trim((string) $line) === '') {
                if ($buffer !== []) {
                    $chunks[] = implode("\n", $buffer);
                    $buffer = [];
                }

                continue;
            }

            $buffer[] = (string) $line;
        }

        if ($buffer !== []) {
            $chunks[] = implode("\n", $buffer);
        }

        if ($chunks === []) {
            return '';
        }

        return collect($chunks)
            ->values()
            ->map(function (string $paragraph, int $index) {
                $style = $index === 0 ? 'margin-top:0;' : 'margin:0 0 16px 0;';
                $content = $this->formatParagraphHtml($paragraph, $index === 0);

                return '<p style="'.$style.'">'.$content.'</p>';
            })
            ->implode('');
    }

    private function formatParagraphHtml(string $paragraph, bool $isFirst): string
    {
        $trimmed = trim($paragraph);

        if ($isFirst && preg_match('/^Olá,\s*(.+?),\s*tudo bem\?$/iu', $trimmed, $matches)) {
            return 'Olá, <strong>'.e(trim($matches[1])).'</strong>, tudo bem?';
        }

        return nl2br(e($trimmed));
    }

    private function stripManualClosing(string $plain): string
    {
        $result = preg_replace(
            '/\s*Atenciosamente,?\s*(\r\n|\r|\n)\s*Equipe\s+.+$/iu',
            '',
            trim($plain)
        );

        return is_string($result) ? trim($result) : trim($plain);
    }

    public function formatManualBodyHtml(string $plain): string
    {
        return $this->formatBodyHtml($this->stripManualClosing($plain));
    }

    public function resolveRecipientName(string $plain, ?string $providedName): string
    {
        $provided = is_string($providedName) ? trim($providedName) : '';
        if ($provided !== '') {
            return $provided;
        }

        $lines = preg_split("/\r\n|\r|\n/", trim($plain)) ?: [];
        $firstLine = trim((string) ($lines[0] ?? ''));

        if ($firstLine !== '' && preg_match('/^Olá,\s*(.+?),\s*tudo bem\?/iu', $firstLine, $matches)) {
            $extracted = trim($matches[1]);
            if ($extracted !== '') {
                return $extracted;
            }
        }

        if ($firstLine !== '' && preg_match('/^Olá,\s*(.+?)![\s]*$/iu', $firstLine, $matches)) {
            $extracted = trim($matches[1]);
            if ($extracted !== '') {
                return $extracted;
            }
        }

        return 'cliente';
    }

    public function applyRecipientNameToGreeting(string $plain, string $name): string
    {
        $lines = preg_split("/\r\n|\r|\n/", $plain, 2);
        $firstLine = trim((string) ($lines[0] ?? ''));

        if ($firstLine === '' || ! preg_match('/^Olá,/iu', $firstLine)) {
            return $plain;
        }

        if (preg_match('/tudo bem\?/iu', $firstLine)) {
            $lines[0] = 'Olá, '.$name.', tudo bem?';
        } elseif (preg_match('/!/u', $firstLine)) {
            $lines[0] = 'Olá, '.$name.'!';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSupportTemplateData(string $plain, ?string $recipientName): array
    {
        $resolvedName = $this->resolveRecipientName($plain, $recipientName);
        $plain = $this->stripManualClosing($plain);
        $paragraphs = $this->splitParagraphs($plain);

        if ($paragraphs !== [] && preg_match('/^Olá,\s*.+,\s*tudo bem\?/iu', $paragraphs[0])) {
            array_shift($paragraphs);
        }

        $closingThanksHtml = '';
        $lastIndex = count($paragraphs) - 1;

        if ($lastIndex >= 0 && preg_match('/^Obrigado por escolher/iu', $paragraphs[$lastIndex])) {
            $closingThanksHtml = $this->formatParagraphsHtml([$paragraphs[$lastIndex]]);
            array_pop($paragraphs);
        }

        $paragraphs = array_values(array_filter(
            $paragraphs,
            static fn (string $paragraph): bool => ! preg_match('/^Queremos garantir/iu', trim($paragraph))
        ));

        $introParts = [];
        $bodyParts = [];
        $inBody = false;

        foreach ($paragraphs as $paragraph) {
            if (! $inBody && preg_match('/^Por favor/iu', trim($paragraph))) {
                $inBody = true;
            }

            if ($inBody) {
                $bodyParts[] = $paragraph;
            } else {
                $introParts[] = $paragraph;
            }
        }

        $beforeWhatsapp = [];
        $afterWhatsapp = [];

        foreach ($bodyParts as $paragraph) {
            $trimmed = trim($paragraph);

            if (preg_match('/^Exemplo:/iu', $trimmed)) {
                continue;
            }

            if (preg_match('/^Assim que/iu', $trimmed)) {
                $afterWhatsapp[] = $paragraph;
            } else {
                $beforeWhatsapp[] = $paragraph;
            }
        }

        $productName = $this->resendConfig->getProductName() ?: $this->resendConfig->getFromName();

        if ($closingThanksHtml === '') {
            $closingThanksHtml = '<p style="margin:0 0 16px 0;">Obrigado por escolher o '
                .e($productName).'. Estamos à disposição para ajudar!</p>';
        }

        return array_merge($this->baseTemplateData(), [
            'recipientName' => $resolvedName,
            'introductionHtml' => $this->formatParagraphsHtml($introParts),
            'messageBodyHtml' => $this->formatParagraphsHtml($beforeWhatsapp),
            'messageAfterWhatsappHtml' => $this->formatParagraphsHtml($afterWhatsapp),
            'closingThanksHtml' => $closingThanksHtml,
            'whatsappUrl' => $this->getWhatsappContactUrl(null, true),
            'showWhatsappButton' => true,
            'manualEmail' => true,
        ]);
    }

    public function isSupportWhatsappBody(string $body): bool
    {
        return (bool) preg_match(
            '/telefone de WhatsApp não ficou disponível|clique no botão abaixo para falar conosco/iu',
            $body
        );
    }

    public function getWhatsappContactUrl(?string $prefilledMessage = null, bool $useFallback = false): ?string
    {
        $digits = $this->normalizeWhatsappNumber($this->resendConfig->getWhatsappNumber());

        if ($digits === null && $useFallback) {
            $digits = self::DEFAULT_WHATSAPP_DIGITS;
        }

        if ($digits === null) {
            return null;
        }

        $message = $prefilledMessage ?? self::WHATSAPP_PREFILL_MESSAGE;

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /**
     * @return list<string>
     */
    private function splitParagraphs(string $plain): array
    {
        $lines = preg_split("/\r\n|\r|\n/", trim($plain)) ?: [];
        $chunks = [];
        $buffer = [];

        foreach ($lines as $line) {
            if (trim((string) $line) === '') {
                if ($buffer !== []) {
                    $chunks[] = implode("\n", $buffer);
                    $buffer = [];
                }

                continue;
            }

            $buffer[] = (string) $line;
        }

        if ($buffer !== []) {
            $chunks[] = implode("\n", $buffer);
        }

        return $chunks;
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function formatParagraphsHtml(array $paragraphs): string
    {
        if ($paragraphs === []) {
            return '';
        }

        return collect($paragraphs)
            ->values()
            ->map(function (string $paragraph) {
                return '<p style="margin:0 0 16px 0;">'.nl2br(e(trim($paragraph))).'</p>';
            })
            ->implode('');
    }

    public function resolveAssetUrl(?string $path, int $minutes = 10080): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $this->minioConfig->applyFilesystemConfig();

        if (Storage::disk('minio_assets')->exists($path)) {
            return Storage::disk('minio_assets')->temporaryUrl($path, now()->addMinutes($minutes));
        }

        if (Storage::disk('public')->exists($path)) {
            return rtrim((string) config('app.url'), '/').'/storage/'.ltrim($path, '/');
        }

        return null;
    }

    /**
     * @return array{path: string, url: string|null}
     */
    private function uploadAsset(string $type, UploadedFile $file, string $settingKey): array
    {
        $this->minioConfig->applyFilesystemConfig();

        $previousPath = PlatformSetting::get($settingKey);
        if (is_string($previousPath) && $previousPath !== '') {
            Storage::disk('minio_assets')->delete($previousPath);
            Storage::disk('public')->delete($previousPath);
        }

        $path = $file->store('platform/email-branding/'.$type, 'minio_assets');
        PlatformSetting::set($settingKey, $path);

        return [
            'path' => $path,
            'url' => $this->resolveAssetUrl($path, 60),
        ];
    }

    private function normalizeWhatsappNumber(?string $number): ?string
    {
        if (! is_string($number) || trim($number) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        return is_string($digits) && $digits !== '' ? $digits : null;
    }
}
