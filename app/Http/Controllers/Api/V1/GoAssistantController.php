<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoAssistantStoreEventRequest;
use App\Http\Resources\Api\V1\GoAssistantEventResource;
use App\Services\GoAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoAssistantController extends Controller
{
    public function __construct(
        private readonly GoAssistantService $assistant,
    ) {}

    /**
     * Últimos acessos (telas e tutoriais) do usuário.
     */
    public function history(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 12);

        $items = $this->assistant->recentHistory($request->user(), $limit);

        return response()->json([
            'data' => GoAssistantEventResource::collection($items)->resolve(),
        ]);
    }

    /**
     * Intents/tutoriais mais usados pelo usuário.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 8);

        return response()->json([
            'data' => $this->assistant->popularIntents($request->user(), $limit),
        ]);
    }

    /**
     * Registra evento (tela, intent, busca, ação, tour, no_match).
     */
    public function store(GoAssistantStoreEventRequest $request): JsonResponse
    {
        $event = $this->assistant->record($request->user(), $request->validated());

        return response()->json([
            'data' => (new GoAssistantEventResource($event))->resolve(),
        ], 201);
    }

    /**
     * Limpa o histórico do usuário.
     */
    public function destroyHistory(Request $request): JsonResponse
    {
        $deleted = $this->assistant->clearHistory($request->user());

        return response()->json([
            'data' => [
                'message' => 'Histórico do Go Assistant removido.',
                'deleted' => $deleted,
            ],
        ]);
    }
}
