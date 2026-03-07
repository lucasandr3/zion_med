<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

    /**
     * Cria um novo template (e opcionalmente seus campos).
     * Campos permitidos: text, textarea, select, checkbox, radio, date, number, file, signature.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
            'public_enabled' => ['boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*.type' => ['required', 'string', Rule::in(['text', 'textarea', 'select', 'checkbox', 'radio', 'date', 'number', 'file', 'signature'])],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.name_key' => ['required', 'string', 'max:80'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['string', 'max:255'],
            'fields.*.sort_order' => ['integer', 'min:0'],
        ]);

        $organizationId = session('current_clinic_id') ?? $request->user()->organization_id ?? $request->user()->clinic_id;
        if (! $organizationId) {
            return response()->json(['message' => 'Organização não definida.'], 422);
        }

        $template = FormTemplate::create([
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'public_enabled' => $validated['public_enabled'] ?? false,
            'created_by' => $request->user()->id,
        ]);

        if (! empty($validated['fields'])) {
            $sortOrder = 0;
            foreach ($validated['fields'] as $f) {
                $optionsJson = isset($f['options']) && is_array($f['options']) ? ['options' => $f['options']] : null;
                FormField::create([
                    'template_id' => $template->id,
                    'type' => $f['type'],
                    'label' => $f['label'],
                    'name_key' => $f['name_key'],
                    'required' => $f['required'] ?? false,
                    'options_json' => $optionsJson,
                    'sort_order' => $f['sort_order'] ?? $sortOrder++,
                ]);
            }
        }

        $template->load('fields');

        return response()->json([
            'data' => (new TemplateResource($template))->toArray($request),
        ], 201);
    }
}
