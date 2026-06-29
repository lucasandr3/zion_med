<?php

namespace App\Support;

class PiiMasker
{
    public static function maskCpf(?string $cpf): ?string
    {
        if ($cpf === null || $cpf === '') {
            return $cpf;
        }

        $digits = preg_replace('/\D/', '', $cpf) ?? '';
        if (strlen($digits) !== 11) {
            return '***.***.***-**';
        }

        return '***.***.***-'.substr($digits, -2);
    }

    public static function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***@***';
        }

        [$local, $domain] = $parts;
        $visible = substr($local, 0, 1);
        if ($visible === '') {
            return '***@'.$domain;
        }

        return $visible.'***@'.$domain;
    }

    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '****';
        }

        return '*****'.substr($digits, -4);
    }

    public static function maskGeneric(?string $value, int $visibleTail = 2): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $len = strlen($value);
        if ($len <= $visibleTail) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', max(4, $len - $visibleTail)).substr($value, -$visibleTail);
    }
}
