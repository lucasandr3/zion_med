<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReleaseNoteRequest;
use App\Http\Resources\Api\V1\ReleaseNoteResource;
use App\Models\ReleaseNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseNotesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notes = ReleaseNote::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 50), 100))
            ->withQueryString();

        return response()->json([
            'data' => ReleaseNoteResource::collection($notes->items()),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ],
        ]);
    }

    public function store(ReleaseNoteRequest $request): JsonResponse
    {
        $note = ReleaseNote::create($request->validated());

        return response()->json([
            'message' => 'Novidade publicada com sucesso.',
            'data' => new ReleaseNoteResource($note),
        ], 201);
    }

    public function update(ReleaseNoteRequest $request, ReleaseNote $releaseNote): JsonResponse
    {
        $releaseNote->update($request->validated());

        return response()->json([
            'message' => 'Novidade atualizada com sucesso.',
            'data' => new ReleaseNoteResource($releaseNote->fresh()),
        ]);
    }

    public function destroy(ReleaseNote $releaseNote): JsonResponse
    {
        $releaseNote->delete();

        return response()->json([
            'message' => 'Novidade removida com sucesso.',
        ]);
    }
}
