<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicFormOtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Envia OTP por e-mail ou WhatsApp para o token do formulário público.
     */
    public function send(Request $request, string $token): JsonResponse
    {
        $key = 'public-form-otp:'.$token;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);
        }

        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(['email', 'whatsapp'])],
            'email' => ['required_if:channel,email', 'nullable', 'email'],
            'phone' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:32'],
        ]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->first();

        if (! $template) {
            return response()->json(['message' => 'Formulário não encontrado.'], 404);
        }

        RateLimiter::hit($key, 60);

        if ($validated['channel'] === 'email') {
            $this->otpService->sendByEmail($token, (string) $validated['email']);
            $message = 'Código enviado por e-mail.';
        } else {
            $this->otpService->sendByWhatsApp($template, $token, (string) $validated['phone']);
            $message = 'Código enviado por WhatsApp.';
        }

        return response()->json([
            'data' => [
                'message' => $message,
                'expires_in_minutes' => OtpService::EXPIRY_MINUTES,
            ],
        ], 201);
    }

    /**
     * Verifica o código OTP.
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(['email', 'whatsapp'])],
            'email' => ['required_if:channel,email', 'nullable', 'email'],
            'phone' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:32'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validated['channel'] === 'email') {
            $recipient = strtolower(trim((string) $validated['email']));
        } else {
            $recipient = $this->otpService->normalizeWhatsappRecipient((string) $validated['phone']);
            if (! $recipient) {
                return response()->json([
                    'message' => 'Número de telefone inválido.',
                    'errors' => ['phone' => ['Informe um celular válido com DDD.']],
                ], 422);
            }
        }

        try {
            $this->otpService->verify($token, $recipient, $validated['code']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Código inválido ou expirado.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'data' => ['message' => 'Código verificado com sucesso.'],
        ]);
    }
}
