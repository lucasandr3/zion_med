<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Lista logs de auditoria da clínica atual.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');

        $organizationId = session('current_clinic_id');
        if (! $organizationId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $logs = AuditLog::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 50), 100))->withQueryString();

        return response()->json([
            'data' => AuditLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }
}
