<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class ErrorHubSanitizer
{
    private const int MAX_STRING_LENGTH = 500;

    private const int MAX_DEPTH = 4;

    /** @var list<string> */
    private const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'authorization',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'apikey',
        'remember_token',
    ];

    public static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($depth > self::MAX_DEPTH) {
            return '[truncado]';
        }

        if ($value instanceof UploadedFile) {
            return [
                '_type' => 'UploadedFile',
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
            ];
        }

        if (is_string($value)) {
            if (strlen($value) <= self::MAX_STRING_LENGTH) {
                return $value;
            }

            return substr($value, 0, self::MAX_STRING_LENGTH).'…';
        }

        if (is_array($value)) {
            $out = [];
            $count = 0;
            foreach ($value as $key => $nested) {
                if ($count >= 20) {
                    break;
                }
                $out[$key] = self::sanitizeKey($key, $nested, $depth);
                $count++;
            }

            return $out;
        }

        if (is_object($value)) {
            return self::sanitize((array) $value, $depth + 1);
        }

        return $value;
    }

    private static function sanitizeKey(string|int $key, mixed $value, int $depth): mixed
    {
        if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
            return '[redacted]';
        }

        return self::sanitize($value, $depth + 1);
    }
}
