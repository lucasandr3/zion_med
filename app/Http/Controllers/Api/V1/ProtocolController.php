<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProtocolDetailResource;
use App\Http\Resources\Api\V1\ProtocolResource;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolController extends Controller
{
    /**
     * Lista protocolos da clínica com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');

        $query = FormSubmission::with('template')->latest();

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $busca = '%' . $request->busca . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('protocol_number', 'like', $busca)
                    ->orWhere('submitter_name', 'like', $busca)
                    ->orWhere('submitter_email', 'like', $busca);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $protocols = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => ProtocolResource::collection($protocols->items()),
            'meta' => [
                'current_page' => $protocols->currentPage(),
                'last_page' => $protocols->lastPage(),
                'per_page' => $protocols->perPage(),
                'total' => $protocols->total(),
            ],
            'links' => [
                'first' => $protocols->url(1),
                'last' => $protocols->url($protocols->lastPage()),
                'prev' => $protocols->previousPageUrl(),
                'next' => $protocols->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Exibe um protocolo com valores e template.
     */
    public function show(FormSubmission $protocol): JsonResponse
    {
        $this->authorize('view-submission', $protocol);
        $protocol->load(['template.fields', 'values', 'template']);

        return response()->json([
            'data' => new ProtocolDetailResource($protocol),
        ]);
    }
}
