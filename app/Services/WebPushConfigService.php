<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\PushSubscription;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\VAPID;

class WebPushConfigService
{
    public const KEY_SUBJECT = 'vapid_subject';

    public const KEY_PUBLIC = 'vapid_public_key';

    public const KEY_PRIVATE = 'vapid_private_key';

    public function getSubject(): string
    {
        $value = PlatformSetting::get(self::KEY_SUBJECT);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $env = env('VAPID_SUBJECT');

        return is_string($env) && $env !== ''
            ? $env
            : 'mailto:suporte@gestgo.com.br';
    }

    public function getPublicKey(): ?string
    {
        $value = PlatformSetting::get(self::KEY_PUBLIC);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->normalizeEnvSecret(env('VAPID_PUBLIC_KEY'));
    }

    public function getPrivateKey(): ?string
    {
        $decrypted = $this->decryptSetting(self::KEY_PRIVATE);
        if ($decrypted !== null) {
            return $decrypted;
        }

        return $this->normalizeEnvSecret(env('VAPID_PRIVATE_KEY'));
    }

    public function isConfigured(): bool
    {
        return filled($this->getPublicKey()) && filled($this->getPrivateKey());
    }

    public function getPrivateKeyPreview(): ?string
    {
        return $this->secretPreview($this->getPrivateKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsPayload(): array
    {
        return [
            'subject' => $this->getSubject(),
            'public_key' => $this->getPublicKey(),
            'private_key_preview' => $this->getPrivateKeyPreview(),
            'configured' => $this->isConfigured(),
            'package_ready' => class_exists(VAPID::class),
        ];
    }

    /**
     * @param  array{subject?: string}  $data
     */
    public function update(array $data): void
    {
        if (array_key_exists('subject', $data)) {
            $subject = is_string($data['subject']) ? trim($data['subject']) : '';
            PlatformSetting::set(
                self::KEY_SUBJECT,
                $subject !== '' ? $subject : 'mailto:suporte@gestgo.com.br'
            );
        }
    }

    /**
     * Gera e persiste um novo par VAPID. Invalida assinaturas antigas.
     *
     * @return array{publicKey: string, privateKey: string}
     */
    public function generateAndStore(?string $subject = null): array
    {
        $keys = $this->createVapidKeys();

        if (is_string($subject) && trim($subject) !== '') {
            PlatformSetting::set(self::KEY_SUBJECT, trim($subject));
        } elseif (PlatformSetting::get(self::KEY_SUBJECT) === null) {
            PlatformSetting::set(self::KEY_SUBJECT, $this->getSubject());
        }

        PlatformSetting::set(self::KEY_PUBLIC, $keys['publicKey']);
        PlatformSetting::set(self::KEY_PRIVATE, Crypt::encryptString($keys['privateKey']));

        if (Schema::hasTable('push_subscriptions')) {
            PushSubscription::query()->delete();
        }

        $this->mergeIntoConfig();

        return $keys;
    }

    public function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        Config::set('webpush.vapid.subject', $this->getSubject());
        Config::set('webpush.vapid.public_key', $this->getPublicKey());
        Config::set('webpush.vapid.private_key', $this->getPrivateKey());
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    public function createVapidKeys(): array
    {
        if (class_exists(VAPID::class)) {
            try {
                $keys = VAPID::createVapidKeys();
                if (! empty($keys['publicKey']) && ! empty($keys['privateKey'])) {
                    return $keys;
                }
            } catch (\Throwable) {
                // fallback Node abaixo
            }
        }

        return $this->createVapidKeysViaNode();
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    private function createVapidKeysViaNode(): array
    {
        $result = Process::timeout(90)
            ->run(['npx', '--yes', 'web-push', 'generate-vapid-keys']);

        if (! $result->successful() && PHP_OS_FAMILY === 'Windows') {
            $result = Process::timeout(90)->run('npx --yes web-push generate-vapid-keys');
        }

        if (! $result->successful()) {
            throw new \RuntimeException(
                'Não foi possível gerar chaves VAPID (OpenSSL/Node). '
                .'Instale OpenSSL no PHP ou Node.js com o pacote web-push.'
            );
        }

        $output = $result->output()."\n".$result->errorOutput();
        if (
            ! preg_match('/Public Key:\s*(\S+)/i', $output, $public)
            || ! preg_match('/Private Key:\s*(\S+)/i', $output, $private)
        ) {
            throw new \RuntimeException('Falha ao interpretar a saída do gerador VAPID.');
        }

        return [
            'publicKey' => $public[1],
            'privateKey' => $private[1],
        ];
    }

    private function decryptSetting(string $key): ?string
    {
        $encrypted = PlatformSetting::get($key);
        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            // Valor legado em texto puro (ex.: migrado do .env)
            return $encrypted;
        }
    }

    private function normalizeEnvSecret(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function secretPreview(?string $secret): ?string
    {
        if ($secret === null || $secret === '') {
            return null;
        }

        if (strlen($secret) <= 8) {
            return '••••••••';
        }

        return substr($secret, 0, 4).'••••'.substr($secret, -4);
    }
}
