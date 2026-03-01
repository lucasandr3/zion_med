<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) !== 11) {
            $fail('O campo :attribute deve ser um CPF válido (11 dígitos).');
            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            $fail('O campo :attribute deve ser um CPF válido.');
            return;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        if ((int) $digits[9] !== $digit1) {
            $fail('O campo :attribute deve ser um CPF válido.');
            return;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $digits[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        if ((int) $digits[10] !== $digit2) {
            $fail('O campo :attribute deve ser um CPF válido.');
        }
    }
}
