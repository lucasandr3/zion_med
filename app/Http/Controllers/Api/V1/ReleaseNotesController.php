<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReleaseNoteResource;
use App\Models\ReleaseNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseNotesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ReleaseNote::query()->where('is_published', true);

        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $term = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('version', 'ilike', $term)
                    ->orWhere('title', 'ilike', $term)
                    ->orWhere('summary', 'ilike', $term)
                    ->orWhereRaw('items::text ilike ?', [$term]);
            });
        }

        $notes = $query
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 20), 50))
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

    public function latest(): JsonResponse
    {
        $latest = ReleaseNote::query()
            ->where('is_published', true)
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'data' => [
                'latest_id' => $latest?->id,
                'latest_version' => $latest?->version,
            ],
        ]);
    }
}
