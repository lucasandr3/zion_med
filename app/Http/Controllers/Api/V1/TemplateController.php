<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Lista templates da clínica.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');
        $query = FormTemplate::query();
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $templates = $query->orderBy('name')->get();

        return response()->json([
            'data' => TemplateResource::collection($templates),
        ]);
    }

    /**
     * Exibe um template.
     */
    public function show(FormTemplate $template): JsonResponse
    {
        $this->authorize('view-template', $template);
        $template->load('fields');

        $resource = (new TemplateResource($template))->toArray(request());
        $resource['fields'] = $template->fields->map(fn ($f) => [
            'id' => $f->id,
            'name_key' => $f->name_key,
            'label' => $f->label,
            'type' => $f->type,
            'sort_order' => $f->sort_order,
        ]);

        return response()->json(['data' => $resource]);
    }
}
