<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login: email + password. Retorna token Sanctum e dados do usuário/clínicas.
     * Para trocar de clínica, use o header X-Clinic-Id nas próximas requisições.
     * Rate limit: 5 req/min por IP (throttle:auth).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::guard('web')->user();

        $user->tokens()->where('name', 'spa')->delete();
        $token = $user->createToken('spa')->plainTextToken;

        $clinics = $this->clinicsForUser($user);
        $currentClinicId = $user->clinic_id;
        if ($clinics->isNotEmpty() && $currentClinicId) {
            session(['current_clinic_id' => $currentClinicId]);
        }

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
                'current_clinic_id' => $currentClinicId,
                'clinics' => ClinicResource::collection($clinics),
            ],
        ]);
    }

    /**
     * Revoga o token atual (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['message' => 'Logout realizado com sucesso.']]);
    }

    /**
     * Envia link de redefinição de senha para o e-mail (para uso pelo front Angular).
     * Rate limit: 5 req/min por IP (throttle:auth).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'data' => ['message' => __($status)],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Redefine a senha com o token recebido por e-mail (para uso pelo front Angular).
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'data' => ['message' => __($status)],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reenvia o e-mail de verificação (usuário autenticado).
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'data' => ['message' => 'E-mail já verificado.'],
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'data' => ['message' => 'Link de verificação reenviado para seu e-mail.'],
        ]);
    }

    /**
     * Verifica o e-mail via link assinado (id, hash, expires, signature).
     * Rota pública: o front chama com os query params da URL do e-mail.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Link inválido ou expirado.'], 403);
        }

        $request->validate([
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        $user = User::find($request->query('id'));
        if (! $user || ! hash_equals((string) $request->query('hash'), sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Link inválido.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'data' => ['message' => 'E-mail já verificado.'],
            ]);
        }

        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));

        return response()->json([
            'data' => ['message' => 'E-mail verificado com sucesso.'],
        ]);
    }

    private function clinicsForUser($user): \Illuminate\Database\Eloquent\Collection
    {
        $tenantId = $user->clinic?->tenant_id;
        if ($tenantId === null) {
            if ($user->clinic_id) {
                return Clinic::where('id', $user->clinic_id)->withCount('users')->get();
            }
            return Clinic::whereRaw('0 = 1')->get();
        }
        return Clinic::withoutGlobalScopes()->where('tenant_id', $tenantId)->orderBy('name')->withCount('users')->get();
    }
}
