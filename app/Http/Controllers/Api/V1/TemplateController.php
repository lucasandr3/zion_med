<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\FormFieldRequest;
use App\Http\Requests\FormTemplateRequest;
use App\Http\Resources\Api\V1\FormFieldResource;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Services\DocumentSendService;
use App\Services\PublicLinkService;
use App\Services\TemplateVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function __construct(
        private PublicLinkService $publicLinkService,
        private TemplateVersionService $templateVersionService,
        private DocumentSendService $documentSendService
    ) {}
    /**
     * Lista sugestões da biblioteca por categoria (estética, odontologia, etc.) para criar templates.
     */
    public function biblioteca(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');
        $categories = FormTemplate::categoryLabels();
        $biblioteca = [
            'estetica' => [
                'label' => $categories['estetica'] ?? 'Estética / Harmonização',
                'templates' => [
                    ['key' => 'consentimento_estetica', 'name' => 'Termo de Consentimento - Procedimento Estético', 'description' => 'Consentimento informado para procedimentos estéticos.'],
                    ['key' => 'anamnese_estetica', 'name' => 'Anamnese - Clínica de Estética', 'description' => 'Ficha de anamnese para avaliação inicial.'],
                    ['key' => 'uso_imagem_estetica', 'name' => 'Autorização de Uso de Imagem', 'description' => 'Termo de autorização para uso de imagens em divulgação.'],
                ],
            ],
            'odontologia' => [
                'label' => $categories['odontologia'] ?? 'Odontologia',
                'templates' => [
                    ['key' => 'consentimento_odontologia', 'name' => 'Termo de Consentimento - Procedimento Odontológico', 'description' => 'Consentimento informado para procedimentos odontológicos.'],
                    ['key' => 'anamnese_odontologia', 'name' => 'Anamnese Odontológica', 'description' => 'Ficha de anamnese odontológica.'],
                    ['key' => 'plano_tratamento', 'name' => 'Acordo de Plano de Tratamento', 'description' => 'Acordo e aceite do plano de tratamento proposto.'],
                ],
            ],
        ];
        if ($request->filled('category')) {
            $cat = $request->category;
            $biblioteca = isset($biblioteca[$cat]) ? [$cat => $biblioteca[$cat]] : $biblioteca;
        }
        return response()->json(['data' => $biblioteca]);
    }

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
            'data' => array_merge(
                (new TemplateResource($template))->toArray($request),
                ['fields' => FormFieldResource::collection($template->fields)->resolve()]
            ),
        ], 201);
    }

    /**
     * Atualiza um template.
     */
    public function update(FormTemplateRequest $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $template->update($request->validated());
        Event::dispatch(new AuditEvent('template.updated', FormTemplate::class, $template->id, null, $template->organization_id ?? $template->clinic_id, $request->user()->id));

        return response()->json([
            'data' => new TemplateResource($template->fresh()),
        ]);
    }

    /**
     * Remove um template.
     */
    public function destroy(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $templateId = $template->id;
        $clinicId = $template->organization_id ?? $template->clinic_id;
        $template->delete();
        Event::dispatch(new AuditEvent('template.deleted', FormTemplate::class, $templateId, null, $clinicId, $request->user()?->id));

        return response()->json(['data' => ['message' => 'Template removido.']], 200);
    }

    /**
     * Cria um template a partir de outro (cópia).
     */
    public function storeFromTemplate(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->authorize('update-template', $template);

        $clinicId = $request->user()->organization_id ?? $request->user()->clinic_id ?? session('current_clinic_id');
        $newTemplate = FormTemplate::create([
            'organization_id' => $clinicId,
            'name' => $template->name,
            'description' => $template->description,
            'category' => null,
            'is_active' => true,
            'public_enabled' => false,
            'created_by' => $request->user()->id,
        ]);

        foreach ($template->fields as $field) {
            FormField::create([
                'template_id' => $newTemplate->id,
                'type' => $field->type,
                'label' => $field->label,
                'name_key' => $field->name_key,
                'required' => $field->required,
                'options_json' => $field->options_json,
                'sort_order' => $field->sort_order,
            ]);
        }

        Event::dispatch(new AuditEvent('template.created', FormTemplate::class, $newTemplate->id, null, $newTemplate->organization_id ?? $newTemplate->clinic_id, $request->user()->id));
        $newTemplate->load('fields');

        return response()->json([
            'data' => array_merge(
                (new TemplateResource($newTemplate))->toArray($request),
                ['fields' => FormFieldResource::collection($newTemplate->fields)->resolve()]
            ),
        ], 201);
    }

    /**
     * Lista campos do template.
     */
    public function campos(FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $template->load('fields');

        return response()->json([
            'data' => FormFieldResource::collection($template->fields),
        ]);
    }

    /**
     * Adiciona um campo ao template.
     */
    public function storeCampo(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $data = $request->validate([
            'type' => ['required', 'string', 'in:text,textarea,number,date,select,checkbox,radio,file,signature'],
            'label' => ['required', 'string', 'max:255'],
            'name_key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('form_fields', 'name_key')->where('template_id', $template->id),
            ],
            'required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'name_key.regex' => 'A chave deve conter apenas letras minúsculas, números e underscore (ex: nome_completo).',
            'name_key.unique' => 'Já existe um campo com esta chave neste template.',
        ]);
        $data['template_id'] = $template->id;
        $data['required'] = (bool) ($data['required'] ?? $request->input('required', false));
        if (! empty($data['options'])) {
            $data['options_json'] = ['options' => array_values($data['options'])];
        }
        unset($data['options']);
        $data['sort_order'] = $data['sort_order'] ?? $template->fields()->max('sort_order') + 1;
        $campo = FormField::create($data);

        return response()->json([
            'data' => new FormFieldResource($campo),
        ], 201);
    }

    /**
     * Atualiza um campo.
     */
    public function updateCampo(FormFieldRequest $request, FormTemplate $template, FormField $campo): JsonResponse
    {
        $this->authorize('update-template', $template);
        $data = $request->validated();
        unset($data['options_text']);
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options_json'] = ['options' => array_values($data['options'])];
            unset($data['options']);
        }
        $campo->update($data);

        return response()->json([
            'data' => new FormFieldResource($campo->fresh()),
        ]);
    }

    /**
     * Remove um campo.
     */
    public function destroyCampo(FormTemplate $template, FormField $campo): JsonResponse
    {
        $this->authorize('update-template', $template);
        $campo->delete();

        return response()->json(['data' => ['message' => 'Campo removido.']], 200);
    }

    /**
     * Gera link público do template e cria versão do template para evidência.
     */
    public function gerarLink(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $this->templateVersionService->getOrCreateCurrentVersion($template);
        $this->publicLinkService->generateToken($template);
        $url = $this->publicLinkService->getPublicUrl($template);

        return response()->json([
            'data' => [
                'message' => 'Link público gerado.',
                'public_url' => $url,
            ],
        ], 200);
    }

    /**
     * Envia o link do documento por e-mail ou WhatsApp (body: channel opcional, recipient_email ou recipient_phone).
     */
    public function enviarDocumento(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'in:email,whatsapp'],
            'recipient_email' => ['required_unless:channel,whatsapp', 'nullable', 'email'],
            'recipient_phone' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
        ]);
        $channel = $validated['channel'] ?? 'email';
        $expiresAt = isset($validated['expires_at']) ? \Carbon\Carbon::parse($validated['expires_at']) : null;
        if ($channel === 'whatsapp') {
            $send = $this->documentSendService->sendByWhatsApp(
                $template,
                $validated['recipient_phone'] ?? '',
                $expiresAt?->toDateTimeImmutable()
            );
            if (! $send) {
                return response()->json(['message' => 'WhatsApp não configurado (N8N_WHATSAPP_WEBHOOK_URL).'], 503);
            }
            return response()->json([
                'data' => [
                    'message' => 'Link enviado por WhatsApp.',
                    'id' => $send->id,
                    'sent_at' => $send->sent_at->toIso8601String(),
                ],
            ], 201);
        }
        $send = $this->documentSendService->sendByEmail(
            $template,
            $validated['recipient_email'] ?? '',
            $validated['recipient_phone'] ?? null,
            $expiresAt?->toDateTimeImmutable()
        );
        return response()->json([
            'data' => [
                'message' => 'Link enviado por e-mail.',
                'id' => $send->id,
                'sent_at' => $send->sent_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Duplica o template (cópia com novo nome opcional).
     */
    public function duplicar(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->authorize('update-template', $template);
        $name = $request->validate(['name' => ['nullable', 'string', 'max:255']])['name'] ?? ($template->name . ' (cópia)');
        $clinicId = $request->user()->organization_id ?? $request->user()->clinic_id ?? session('current_clinic_id');
        $newTemplate = FormTemplate::create([
            'organization_id' => $clinicId,
            'name' => $name,
            'description' => $template->description,
            'category' => $template->category,
            'is_active' => true,
            'public_enabled' => false,
            'created_by' => $request->user()->id,
        ]);
        foreach ($template->fields as $field) {
            FormField::create([
                'template_id' => $newTemplate->id,
                'type' => $field->type,
                'label' => $field->label,
                'name_key' => $field->name_key,
                'required' => $field->required,
                'options_json' => $field->options_json,
                'sort_order' => $field->sort_order,
            ]);
        }
        Event::dispatch(new AuditEvent('template.created', FormTemplate::class, $newTemplate->id, null, $newTemplate->organization_id ?? $newTemplate->clinic_id, $request->user()->id));
        $newTemplate->load('fields');
        return response()->json([
            'data' => array_merge(
                (new TemplateResource($newTemplate))->toArray($request),
                ['fields' => FormFieldResource::collection($newTemplate->fields)->resolve()]
            ),
        ], 201);
    }

    /**
     * Desativa o link público do template.
     */
    public function desativarLink(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $this->publicLinkService->disablePublicLink($template);

        return response()->json([
            'data' => ['message' => 'Link público desativado.'],
        ], 200);
    }
}
