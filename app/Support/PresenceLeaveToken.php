<?php

namespace App\Support;

use App\Models\User;

/**
 * Token de curta duração para sendBeacon de presença (evita enviar o Sanctum plain token no body).
 */
final class PresenceLeaveToken
{
    private const TTL_SECONDS = 60 * 60 * 48;

    public static function issue(User $user, int $organizationId): string
    {
        $exp = time() + self::TTL_SECONDS;
        $payload = $user->id.'|'.$organizationId.'|'.$exp;
        $sig = hash_hmac('sha256', $payload, self::key());

        return rtrim(strtr(base64_encode($payload.'|'.$sig), '+/', '-_'), '=');
    }

    /**
     * @return array{user_id: int, organization_id: int}|null
     */
    public static function parse(string $token): ?array
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        $parts = explode('|', $raw);
        if (count($parts) !== 4) {
            return null;
        }

        [$userId, $organizationId, $exp, $sig] = $parts;
        if (! ctype_digit((string) $userId) || ! ctype_digit((string) $organizationId) || ! ctype_digit((string) $exp)) {
            return null;
        }

        $payload = $userId.'|'.$organizationId.'|'.$exp;
        $expected = hash_hmac('sha256', $payload, self::key());
        if (! hash_equals($expected, (string) $sig)) {
            return null;
        }

        if ((int) $exp < time()) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'organization_id' => (int) $organizationId,
        ];
    }

    private static function key(): string
    {
        return (string) config('app.key');
    }
}
