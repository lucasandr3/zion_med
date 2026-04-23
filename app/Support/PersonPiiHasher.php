<?php

namespace App\Support;

final class PersonPiiHasher
{
    public static function cpf(string $digits11): string
    {
        return hash_hmac('sha256', $digits11, (string) config('app.key'));
    }

    public static function email(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), (string) config('app.key'));
    }
}
