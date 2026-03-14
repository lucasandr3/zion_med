<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login: email + password. Retorna token Sanctum e dados do usuário/clínicas.
     * Para trocar de clínica, use o header X-Clinic-Id nas próximas requisições.
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
