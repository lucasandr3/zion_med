<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\OtpChallenge;
use App\Models\Organization;
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
     * Normaliza telefone para envio via Evolution (apenas dígitos, DDI 55 quando faltar).
     */
    public function normalizeWhatsappRecipient(string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', $raw) ?? '';
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '55') && strlen($d) >= 12) {
            return $d;
        }
        if (strlen($d) >= 10 && strlen($d) <= 11) {
            return '55'.$d;
        }

        return null;
    }

    /**
     * Gera e envia OTP por WhatsApp (Evolution Go) para o número informado.
     */
    public function sendByWhatsApp(FormTemplate $template, string $token, string $phoneRaw): OtpChallenge
    {
        $orgId = $template->organization_id ?? $template->clinic_id;
        $organization = $orgId ? Organization::query()->find($orgId) : null;
        if (! $organization || ! $organization->evolution_go_instance_token) {
            throw ValidationException::withMessages([
                'phone' => ['WhatsApp (OTP) não está configurado para esta clínica.'],
            ]);
        }

        $number = $this->normalizeWhatsappRecipient($phoneRaw);
        if (! $number) {
            throw ValidationException::withMessages([
                'phone' => ['Informe um celular válido com DDD (ex.: 11999998888).'],
            ]);
        }

        $client = app(EvolutionGoClient::class);
        if (! $client->isConfigured()) {
            throw ValidationException::withMessages([
                'phone' => ['Serviço de mensagens indisponível.'],
            ]);
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes(self::EXPIRY_MINUTES);

        $challenge = OtpChallenge::create([
            'token' => $token,
            'channel' => 'whatsapp',
            'recipient' => $number,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $brand = (string) (config('mail.branding.product_name') ?: config('asaas.product_name') ?: config('app.name'));
        $text = "Seu código de verificação {$brand}: {$code}. Válido por ".self::EXPIRY_MINUTES.' minutos. Não compartilhe.';

        $client->sendText((string) $organization->evolution_go_instance_token, $number, $text);

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
