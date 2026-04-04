<?php

namespace App\Services;

use App\Models\OtpChallenge;
use App\Support\MailBrand;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const CODE_LENGTH = 6;
    public const EXPIRY_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;

    /**
     * Gera e envia OTP por e-mail para o token (ex.: token do formulário público).
     */
    public function sendByEmail(string $token, string $email): OtpChallenge
    {
        $code = $this->generateCode();
        $expiresAt = now()->addMinutes(self::EXPIRY_MINUTES);

        $challenge = OtpChallenge::create([
            'token' => $token,
            'channel' => 'email',
            'recipient' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $brand = (string) (config('mail.branding.product_name') ?: config('asaas.product_name') ?: config('app.name'));
        Mail::send(
            'emails.otp-code',
            MailBrand::with([
                'emailTitle' => 'Código de verificação',
                'code' => $code,
                'validMinutes' => self::EXPIRY_MINUTES,
            ]),
            function ($message) use ($email, $brand) {
                $message->to($email)
                    ->subject("Código de verificação — {$brand}");
            }
        );

        return $challenge;
    }

    /**
     * Verifica o código OTP. Lança ValidationException se inválido.
     */
    public function verify(string $token, string $recipient, string $code): OtpChallenge
    {
        $challenge = OtpChallenge::where('token', $token)
            ->where('recipient', $recipient)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $challenge) {
            throw ValidationException::withMessages(['code' => ['Código não encontrado ou já utilizado.']]);
        }

        if ($challenge->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => ['Código expirado. Solicite um novo.']]);
        }

        if ($challenge->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages(['code' => ['Máximo de tentativas excedido. Solicite um novo código.']]);
        }

        $challenge->increment('attempts');

        if (! hash_equals((string) $challenge->code, (string) $code)) {
            throw ValidationException::withMessages(['code' => ['Código inválido.']]);
        }

        $challenge->markVerified();

        return $challenge;
    }

    /**
     * Verifica se existe um OTP válido (já verificado) para este token + destinatário.
     */
    public function isVerified(string $token, string $recipient): bool
    {
        return OtpChallenge::where('token', $token)
            ->where('recipient', $recipient)
            ->whereNotNull('verified_at')
            ->exists();
    }

    private function generateCode(): string
    {
        $digits = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        return $digits;
    }
}
