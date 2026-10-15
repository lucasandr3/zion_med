<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * URLs públicas de Link Bio / CTA: só https (http fora de prod), mailto e tel.
 */
final class SafePublicLinkUrl
{
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 500) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $allowed = ['https', 'mailto', 'tel'];
        if (! app()->environment('production')) {
            $allowed[] = 'http';
        }

        if (! in_array($scheme, $allowed, true)) {
            return false;
        }

        if (in_array($scheme, ['http', 'https'], true) && empty($parts['host'])) {
            return false;
        }

        return true;
    }

    public static function assertAllowed(string $url, string $field = 'url'): void
    {
        if (! self::isAllowed($url)) {
            throw ValidationException::withMessages([
                $field => ['Use apenas links https, mailto ou tel.'],
            ]);
        }
    }
}
