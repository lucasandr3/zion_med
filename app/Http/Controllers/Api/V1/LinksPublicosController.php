<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinksPublicosController extends Controller
{
    /**
     * Lista templates com link público ativo (para copiar/enviar).
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('manage-templates') && ! $request->user()->can('view-submissions')) {
            abort(403);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        $paginator = FormTemplate::query()
            ->whereNotNull('public_token')
            ->where('public_enabled', true)
            ->withCount('submissions')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $data = collect($paginator->items())->map(function (FormTemplate $t) use ($base) {
            return [
                'id' => $t->id,
                'template_id' => $t->id,
                'name' => $t->name,
                'template_name' => $t->name,
                'category_label' => $t->category_label,
                'public_url' => $base.'/f/'.$t->public_token,
                'public_token' => $t->public_token,
                'submission_count' => (int) $t->submissions_count,
                'updated_at' => $t->updated_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
