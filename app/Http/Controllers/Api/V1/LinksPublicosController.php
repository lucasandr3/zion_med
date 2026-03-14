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

        $templates = FormTemplate::whereNotNull('public_token')
            ->where('public_enabled', true)
            ->orderBy('name')
            ->get();

        $data = $templates->map(function (FormTemplate $t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'public_url' => route('formulario-publico.show', ['token' => $t->public_token]),
                'public_token' => $t->public_token,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
