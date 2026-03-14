<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChooseClinicController extends Controller
{
    /**
     * Lista clínicas que o usuário pode escolher (para trocar contexto).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $clinics = $this->clinicsAllowedForUser($user);
        $currentClinicId = session('current_clinic_id');

        return response()->json([
            'data' => [
                'clinics' => ClinicResource::collection($clinics),
                'current_clinic_id' => $currentClinicId,
            ],
        ]);
    }

    /**
     * Define a clínica atual. Nas próximas requisições, envie o header X-Clinic-Id com o id escolhido.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $allowedClinicIds = $this->clinicsAllowedForUser($user)->pluck('id')->all();

        if (empty($allowedClinicIds)) {
            return response()->json(['message' => 'Nenhuma empresa disponível para seleção.'], 403);
        }

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'in:' . implode(',', $allowedClinicIds)],
        ]);

        session(['current_clinic_id' => $validated['clinic_id']]);

        return response()->json([
            'data' => [
                'message' => 'Empresa alterada. Use o header X-Clinic-Id nas próximas requisições com o valor ' . $validated['clinic_id'],
                'current_clinic_id' => $validated['clinic_id'],
            ],
        ]);
    }

    private function authorizeClinicSwitch(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($user->canSwitchClinic()) {
            return;
        }
        $clinic = $user->clinic;
        if ($clinic?->tenant_id && Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->count() > 1) {
            return;
        }
        abort(403);
    }

    private function clinicsAllowedForUser($user): \Illuminate\Database\Eloquent\Collection
    {
        $tenantId = $user->clinic?->tenant_id;
        if ($tenantId === null) {
            return $user->clinic_id
                ? Clinic::where('id', $user->clinic_id)->withCount('users')->get()
                : Clinic::whereRaw('0 = 1')->withCount('users')->get();
        }
        return Clinic::withoutGlobalScopes()->where('tenant_id', $tenantId)->orderBy('name')->withCount('users')->get();
    }
}
