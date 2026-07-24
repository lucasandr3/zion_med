<?php

namespace App\Support;

/**
 * Valida URLs de webhook/outbound contra SSRF (schemes, IPs privados, link-local, metadata).
 */
final class SafeOutboundUrl
{
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['https', 'http'], true)) {
            return false;
        }

        // Em produção preferir HTTPS; HTTP permitido só em local/testing.
        if ($scheme === 'http' && app()->environment('production')) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || $host === '0.0.0.0') {
            return ! app()->environment('production');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        $ips = gethostbynamel($host) ?: [];
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public static function assertAllowed(string $url): void
    {
        if (! self::isAllowed($url)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'url' => ['URL de webhook inválida ou não permitida (use HTTPS público).'],
            ]);
        }
    }

    private static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
            // Metadata cloud / link-local
            if (str_starts_with($ip, '169.254.')) {
                return false;
            }

            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }
}
