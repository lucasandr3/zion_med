<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Retorna o usuário autenticado e a clínica atual do contexto.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $clinicId = session('current_clinic_id');
        $clinic = $clinicId ? Clinic::find($clinicId) : null;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'clinic' => $clinic ? [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                ] : null,
            ],
        ]);
    }
}
