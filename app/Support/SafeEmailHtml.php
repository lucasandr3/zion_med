<?php

namespace App\Support;

/**
 * Sanitiza HTML permitido em e-mails transacionais (allowlist de tags).
 */
final class SafeEmailHtml
{
    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><span><div><h1><h2><h3><h4><table><thead><tbody><tr><th><td>';
        $clean = strip_tags($html, $allowed);

        // Remove on* handlers and javascript: URLs
        $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/i', '$1="#"', $clean) ?? $clean;

        return $clean;
    }
}
