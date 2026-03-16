<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PublicFormOtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Envia OTP por e-mail para o token do formulário público.
     */
    public function send(Request $request, string $token): JsonResponse
    {
        $key = 'public-form-otp:' . $token;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->first();

        if (! $template) {
            return response()->json(['message' => 'Formulário não encontrado.'], 404);
        }

        RateLimiter::hit($key, 60);

        $this->otpService->sendByEmail($token, $validated['email']);

        return response()->json([
            'data' => [
                'message' => 'Código enviado por e-mail.',
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
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $this->otpService->verify($token, $validated['email'], $validated['code']);
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
