<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Http\Resources\Api\V1\UserResource;
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
                'user' => new UserResource($user),
                'clinic' => $clinic ? new ClinicResource($clinic) : null,
            ],
        ]);
    }
}
