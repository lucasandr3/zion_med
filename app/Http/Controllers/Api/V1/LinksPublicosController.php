<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LinksPublicosIndexRequest;
use App\Models\FormTemplate;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;

class LinksPublicosController extends Controller
{
    /**
     * Lista templates com link público ativo (para copiar/enviar).
     */
    public function index(LinksPublicosIndexRequest $request): JsonResponse
    {
        $paginator = FormTemplate::query()
            ->whereNotNull('public_token')
            ->where('public_enabled', true)
            ->withCount('submissions')
            ->orderBy('name')
            ->paginate($request->perPage())
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

        return response()->json(
            ApiPagination::wrap($paginator, $data)
        );
    }
}
