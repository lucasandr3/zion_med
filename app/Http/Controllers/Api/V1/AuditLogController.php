<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogIndexRequest;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Lista logs de auditoria da clínica atual.
     */
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        if (! session('current_clinic_id')) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $paginator = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($request->perPage())
            ->withQueryString();

        return response()->json(
            ApiPagination::wrap($paginator, AuditLogResource::collection($paginator->items()))
        );
    }
}
