<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AuditEvent;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\FormFieldRequest;
use App\Http\Requests\FormTemplateRequest;
use App\Http\Requests\TemplateBibliotecaRequest;
use App\Http\Requests\TemplateIndexRequest;
use App\Http\Resources\Api\V1\FormFieldResource;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\Organization;
use App\Models\TemplateCategory;
use App\Services\ClinicalStepStructureService;
use App\Services\ClinicalStepValidationService;
use App\Services\ComprehensionQuizService;
use App\Services\DocumentSendService;
use App\Services\PublicLinkService;
use App\Services\TemplateLibraryCatalog;
use App\Services\TemplateVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\ClinicalStepKind;

class TemplateController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(
        private PublicLinkService $publicLinkService,
        private TemplateVersionService $templateVersionService,
        private DocumentSendService $documentSendService,
        private TemplateLibraryCatalog $libraryCatalog,
        private ClinicalStepValidationService $clinicalStepValidationService,
        private ClinicalStepStructureService $clinicalStepStructureService,
    ) {}
    /**
     * Lista categorias de templates da organização atual.
     */
    public function categories(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');

        $organizationId = (int) ($this->currentOrganizationId($request) ?? 0);
        if ($organizationId <= 0) {
            return response()->json(['data' => []]);
        }

        $knownKeys = TemplateCategory::query()
            ->where('organization_id', $organizationId)
            ->pluck('key')
            ->all();

        $knownMap = array_fill_keys($knownKeys, true);
        $labels = FormTemplate::categoryLabels();

        $templateKeys = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->all();

        foreach ($templateKeys as $key) {
            if (isset($knownMap[$key])) {
                continue;
            }

            TemplateCategory::query()->create([
                'organization_id' => $organizationId,
                'key' => $key,
                'name' => $labels[$key] ?? $key,
            ]);
            $knownMap[$key] = true;
        }

        $items = TemplateCategory::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['key', 'name'])
            ->map(fn (TemplateCategory $c): array => ['key' => $c->key, 'name' => $c->name])
            ->values();

        return response()->json(['data' => $items]);
    }

    /**
     * Catálogo curado da biblioteca por especialidade, com metadados de revisão jurídica/clínica.
     */
    public function biblioteca(TemplateBibliotecaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $orgId = (int) ($this->currentOrganizationId($request) ?? 0);
        $niche = 'estetica';
        if ($orgId > 0) {
            $niche = (string) (Organization::query()->whereKey($orgId)->value('niche') ?: 'estetica');
        }
        if (! empty($validated['niche'])) {
            $niche = (string) $validated['niche'];
        }

        $payload = $this->libraryCatalog->catalog(
            $niche,
            $validated['category'] ?? null,
            $orgId > 0 ? $orgId : null,
        );

        return response()->json(['data' => $payload]);
    }

    /**
     * Detalhe de um item da biblioteca (inclui campos para pré-visualização).
     */
    public function bibliotecaShow(Request $request, string $libraryKey): JsonResponse
    {
        $this->authorize('manage-templates');
        $item = $this->libraryCatalog->findByKey($libraryKey);
        if ($item === null) {
            return response()->json(['message' => 'Modelo da biblioteca não encontrado.'], 404);
        }

        $orgId = (int) ($this->currentOrganizationId($request) ?? 0);
        if ($orgId > 0) {
            $installed = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->where('library_key', $item['library_key'])
                ->value('id');
            $item['installed_template_id'] = $installed;
        }

        return response()->json(['data' => $item]);
    }

    /**
     * Instala um modelo da biblioteca na organização atual.
     */
    public function installFromLibrary(Request $request, string $libraryKey): JsonResponse
    {
        $this->authorize('manage-templates');
        $orgId = (int) ($this->currentOrganizationId($request) ?? 0);
        if ($orgId <= 0) {
            return response()->json(['message' => 'Organização não encontrada.'], 422);
        }

        $organization = Organization::query()->findOrFail($orgId);

        try {
            $template = $this->libraryCatalog->install($organization, $libraryKey, $request->user()?->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        Event::dispatch(new AuditEvent('template.created', FormTemplate::class, $template->id, [
            'source' => 'library',
            'library_key' => $libraryKey,
        ], $template->organization_id ?? $template->clinic_id, $request->user()?->id));

        $this->templateVersionService->ensureSyncedVersion($template);

        return response()->json([
            'data' => array_merge(
                (new TemplateResource($template))->toArray($request),
                ['fields' => FormFieldResource::collection($template->fields)->resolve()]
            ),
        ], 201);
    }

    /**
     * Lista templates da clínica.
     */
    public function index(TemplateIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = FormTemplate::query();
        $activeFilter = $request->isActiveFilter();
        if ($activeFilter !== null) {
            $query->where('is_active', $activeFilter);
        }
        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        } else {
            $orgId = (int) ($this->currentOrganizationId($request) ?? 0);
            $niche = 'estetica';
            if ($orgId > 0) {
                $niche = (string) (Organization::query()->whereKey($orgId)->value('niche') ?: 'estetica');
            }
            $query->visibleForNiche($niche);
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
            'public_require_person_link' => ['boolean'],
            'new_category' => ['nullable', 'string', 'max:120'],
            'fields' => ['nullable', 'array'],
            'fields.*.type' => ['required', 'string', Rule::in(['text', 'textarea', 'select', 'checkbox', 'radio', 'date', 'number', 'file', 'signature', 'heading', 'notice', 'section_break'])],
            'fields.*.label' => ['required', 'string', 'max:2000'],
            'fields.*.name_key' => ['required', 'string', 'max:80'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['string', 'max:255'],
            'fields.*.sort_order' => ['integer', 'min:0'],
        ]);

        $organizationId = $this->currentOrganizationId($request);
        if (! $organizationId) {
            return response()->json(['message' => 'Organização não definida.'], 422);
        }

        $categoryInput = trim((string) ($validated['new_category'] ?? $validated['category'] ?? ''));
        $categoryKey = null;
        if ($categoryInput !== '') {
            $normalized = str_replace('-', '_', Str::slug($categoryInput, '_'));
            $categoryKey = mb_substr($normalized !== '' ? $normalized : $categoryInput, 0, 80);

            TemplateCategory::query()->updateOrCreate(
                [
                    'organization_id' => (int) $organizationId,
                    'key' => $categoryKey,
                ],
                [
                    'name' => mb_substr($categoryInput, 0, 120),
                ]
            );
        }

        $template = FormTemplate::create([
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $categoryKey,
            'is_active' => $validated['is_active'] ?? true,
            'public_enabled' => $validated['public_enabled'] ?? false,
            'public_require_person_link' => $validated['public_require_person_link'] ?? false,
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
        $validated = $request->validated();
        $organizationId = (int) ($template->organization_id ?? $this->currentOrganizationId($request) ?? 0);

        $hasCategoryInput = array_key_exists('category', $validated) || array_key_exists('new_category', $validated);
        if ($hasCategoryInput) {
            $categoryInput = trim((string) ($validated['new_category'] ?? $validated['category'] ?? ''));
            $categoryKey = null;
            if ($categoryInput !== '') {
                $normalized = str_replace('-', '_', Str::slug($categoryInput, '_'));
                $categoryKey = mb_substr($normalized !== '' ? $normalized : $categoryInput, 0, 80);

                if ($organizationId > 0) {
                    TemplateCategory::query()->updateOrCreate(
                        [
                            'organization_id' => $organizationId,
                            'key' => $categoryKey,
                        ],
                        [
                            'name' => mb_substr($categoryInput, 0, 120),
                        ]
                    );
                }
            }
            $validated['category'] = $categoryKey;
        }

        unset($validated['new_category']);

        if (($validated['category'] ?? null) === 'consentimento' && empty($validated['document_kind'])) {
            $validated['document_kind'] = 'consentimento';
        }

        $kind = $validated['document_kind'] ?? $template->document_kind;
        if ($kind !== 'consentimento') {
            $validated['consent_validity_days'] = null;
            $validated['comprehension_quiz'] = null;
        } else {
            if (array_key_exists('comprehension_quiz', $validated)) {
                $validated['comprehension_quiz'] = app(ComprehensionQuizService::class)->normalize($validated['comprehension_quiz']);
                if ($validated['comprehension_quiz'] === []) {
                    $validated['comprehension_quiz'] = null;
                }
            }
        }

        $template->update($validated);
        $this->templateVersionService->ensureSyncedVersion($template->fresh(['fields']));
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

        $clinicId = $this->currentOrganizationId($request);
        $newTemplate = FormTemplate::create([
            'organization_id' => $clinicId,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'document_kind' => $template->document_kind,
            'library_key' => $template->library_key,
            'library_content_version' => $template->library_content_version,
            'legal_review_status' => $template->legal_review_status,
            'clinical_review_status' => $template->clinical_review_status,
            'library_reviewed_at' => $template->library_reviewed_at,
            'is_active' => true,
            'public_enabled' => false,
            'public_require_person_link' => false,
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
            'type' => ['required', 'string', 'in:text,textarea,number,date,select,checkbox,radio,file,signature,heading,notice,section_break'],
            'label' => ['required', 'string', 'max:2000'],
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
            'visibility_rules' => ['nullable', 'array'],
            'visibility_rules.show_when' => ['nullable', 'array'],
            'visibility_rules.show_when.*.field' => ['required_with:visibility_rules.show_when', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'visibility_rules.show_when.*.operator' => ['required_with:visibility_rules.show_when', 'string', 'in:equals,not_equals,filled,empty'],
            'visibility_rules.show_when.*.value' => ['nullable', 'string', 'max:255'],
            'clinical_step_kind' => ['nullable', 'string', 'max:40', Rule::in(ClinicalStepKind::all())],
        ], [
            'name_key.regex' => 'A chave deve conter apenas letras minúsculas, números e underscore (ex: nome_completo).',
            'name_key.unique' => 'Já existe um campo com esta chave neste template.',
        ]);
        $data['template_id'] = $template->id;
        $structural = in_array($data['type'], ['heading', 'notice', 'section_break'], true);
        $data['required'] = $structural ? false : (bool) ($data['required'] ?? $request->input('required', false));
        if (! empty($data['options'])) {
            $data['options_json'] = ['options' => array_values($data['options'])];
        }
        unset($data['options']);
        $data['sort_order'] = $data['sort_order'] ?? $template->fields()->max('sort_order') + 1;
        $campo = FormField::create($data);
        $this->templateVersionService->ensureSyncedVersion($template->fresh(['fields']));

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
        if (isset($data['type']) && in_array($data['type'], ['heading', 'notice', 'section_break'], true)) {
            $data['required'] = false;
        }
        $campo->update($data);
        $this->templateVersionService->ensureSyncedVersion($template->fresh(['fields']));

        return response()->json([
            'data' => new FormFieldResource($campo->fresh()),
        ]);
    }

    /**
     * Reordena campos do template (ids na ordem desejada).
     */
    public function reorderCampos(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $ownedIds = $template->fields()->whereIn('id', $ids)->pluck('id')->all();
        if (count($ownedIds) !== count(array_unique($ids))) {
            return response()->json(['message' => 'Um ou mais campos não pertencem a este modelo.'], 422);
        }

        foreach (array_values($ids) as $order => $id) {
            FormField::where('id', $id)
                ->where('template_id', $template->id)
                ->update(['sort_order' => $order + 1]);
        }

        $this->templateVersionService->ensureSyncedVersion($template->fresh(['fields']));

        return response()->json(['data' => ['message' => 'Ordem atualizada.']]);
    }

    /**
     * Remove um campo.
     */
    public function destroyCampo(FormTemplate $template, FormField $campo): JsonResponse
    {
        $this->authorize('update-template', $template);
        $campo->delete();
        $this->templateVersionService->ensureSyncedVersion($template->fresh(['fields']));

        return response()->json(['data' => ['message' => 'Campo removido.']], 200);
    }

    /**
     * Lista versões persistidas do template (metadados, sem snapshot completo).
     */
    public function listVersoes(FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);

        $versions = FormTemplateVersion::query()
            ->where('form_template_id', $template->id)
            ->orderByDesc('version')
            ->get(['id', 'version', 'name', 'created_at']);

        return response()->json([
            'data' => $versions->map(fn (FormTemplateVersion $v) => [
                'id' => $v->id,
                'version' => (int) $v->version,
                'name' => $v->name,
                'created_at' => $v->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Compara duas versões do template (default: penúltima vs última).
     */
    public function compararVersoes(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);

        $validated = $request->validate([
            'from' => ['nullable', 'integer'],
            'to' => ['nullable', 'integer'],
        ]);

        $this->templateVersionService->getOrCreateCurrentVersion($template);

        $versions = FormTemplateVersion::query()
            ->where('form_template_id', $template->id)
            ->orderByDesc('version')
            ->get();

        if ($versions->count() < 2) {
            $only = $versions->first();

            return response()->json([
                'data' => [
                    'from' => null,
                    'to' => $only ? [
                        'id' => $only->id,
                        'version' => (int) $only->version,
                        'created_at' => $only->created_at?->toIso8601String(),
                    ] : null,
                    'meta' => ['name_changed' => false, 'description_changed' => false],
                    'fields' => ['added' => [], 'removed' => [], 'changed' => [], 'reordered' => []],
                    'has_changes' => false,
                ],
            ]);
        }

        $toVersion = isset($validated['to'])
            ? $versions->firstWhere('id', (int) $validated['to'])
            : $versions->first();
        $fromVersion = isset($validated['from'])
            ? $versions->firstWhere('id', (int) $validated['from'])
            : $versions->skip(1)->first();

        if (! $fromVersion instanceof FormTemplateVersion || ! $toVersion instanceof FormTemplateVersion) {
            return response()->json(['message' => 'Versão não encontrada para este template.'], 404);
        }

        $diff = $this->templateVersionService->compareVersions($fromVersion, $toVersion);

        return response()->json([
            'data' => [
                'from' => [
                    'id' => $fromVersion->id,
                    'version' => (int) $fromVersion->version,
                    'created_at' => $fromVersion->created_at?->toIso8601String(),
                ],
                'to' => [
                    'id' => $toVersion->id,
                    'version' => (int) $toVersion->version,
                    'created_at' => $toVersion->created_at?->toIso8601String(),
                ],
                ...$diff,
            ],
        ]);
    }

    /**
     * Gera link público do template e cria versão do template para evidência.
     */
    public function gerarLink(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $issues = $this->clinicalStepValidationService->validateForPublish($template);
        $blocking = array_values(array_filter(
            $issues,
            fn ($i) => in_array($i['code'], $this->clinicalStepValidationService->blockingCodes(), true)
        ));
        if ($blocking !== []) {
            return response()->json([
                'message' => $blocking[0]['message'],
                'clinical_validation' => $issues,
            ], 422);
        }
        $this->templateVersionService->getOrCreateCurrentVersion($template);
        $this->publicLinkService->generateToken($template);
        $url = $this->publicLinkService->getPublicUrl($template);

        return response()->json([
            'data' => [
                'message' => 'Link público gerado.',
                'public_url' => $url,
                'clinical_validation' => $issues,
            ],
        ], 200);
    }

    /**
     * Valida estrutura de etapas clínicas antes de publicar.
     */
    public function validarEtapasClinicas(FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $issues = $this->clinicalStepValidationService->validateForPublish($template);

        return response()->json([
            'data' => [
                'issues' => $issues,
                'has_blocking' => array_filter(
                    $issues,
                    fn ($i) => in_array($i['code'], $this->clinicalStepValidationService->blockingCodes(), true)
                ) !== [],
            ],
        ]);
    }

    /**
     * Aplica estrutura TCLE (etapas clínicas) nos campos existentes.
     */
    public function aplicarEstruturaTcle(FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $updated = $this->clinicalStepStructureService->applyTcleStructure($template);
        $this->templateVersionService->ensureSyncedVersion($updated->fresh(['fields']));

        return response()->json([
            'data' => [
                'message' => 'Estrutura de etapas clínicas aplicada.',
                'template' => new TemplateResource($updated->load('fields')),
            ],
        ]);
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
                return response()->json(['message' => 'WhatsApp não configurado para esta clínica (integração Evolution Go) ou número inválido.'], 503);
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
        $clinicId = $this->currentOrganizationId($request);
        $newTemplate = FormTemplate::create([
            'organization_id' => $clinicId,
            'name' => $name,
            'description' => $template->description,
            'category' => $template->category,
            'is_active' => true,
            'public_enabled' => false,
            'public_require_person_link' => $template->public_require_person_link ?? false,
            'public_person_link_mode' => $template->public_person_link_mode ?? 'code',
            'document_kind' => $template->document_kind ?: ($template->category === 'consentimento' ? 'consentimento' : 'ficha'),
            'consent_validity_days' => $template->consent_validity_days,
            'comprehension_quiz' => $template->comprehension_quiz,
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
        $this->templateVersionService->ensureSyncedVersion($newTemplate->fresh(['fields']));
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
