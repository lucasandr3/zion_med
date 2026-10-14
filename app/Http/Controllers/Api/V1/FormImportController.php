<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\FormImport;
use App\Services\FormImport\FormImportService;
use App\Services\FormImportConfigService;
use App\Services\ProductEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormImportController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(
        private FormImportService $imports,
        private ProductEventService $productEvents,
        private FormImportConfigService $formImportConfig,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');
        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $maxKb = (int) config('form_import.max_upload_kb', 10240);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:pdf,jpg,jpeg,png,txt'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
            'consent_blank_ack' => ['accepted'],
        ], [
            'consent_blank_ack.accepted' => 'Confirme que o arquivo é uma ficha em branco, sem dados de paciente.',
        ]);

        $import = $this->imports->createFromUpload(
            (int) $orgId,
            (int) $request->user()->id,
            $data['file'],
            $data['idempotency_key'] ?? null,
        );

        return response()->json(['data' => $this->serialize($import->fresh())], 201);
    }

    public function show(Request $request, FormImport $formImport): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        return response()->json(['data' => $this->serialize($formImport)]);
    }

    public function updateDraft(Request $request, FormImport $formImport): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        $data = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.name_key' => ['nullable', 'string'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.visibility_rules' => ['nullable', 'array'],
            'fields.*.sort_order' => ['nullable', 'integer'],
            'fields.*.page' => ['nullable', 'integer'],
        ]);

        try {
            $import = $this->imports->saveDraft($formImport, ['fields' => $data['fields']]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($import)]);
    }

    public function commit(Request $request, FormImport $formImport): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'fields' => ['nullable', 'array'],
        ]);

        if (! empty($data['fields'])) {
            try {
                $this->imports->saveDraft($formImport, ['fields' => $data['fields']]);
                $formImport->refresh();
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        try {
            $template = $this->imports->commit($formImport, $data['name'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->productEvents->record(
            'template_created',
            (int) $formImport->organization_id,
            $request->user()?->id ? (int) $request->user()->id : null,
            ['template_id' => $template->id, 'source' => 'form_import'],
            'template_created:tpl:'.$template->id,
            ['channel' => 'app']
        );

        return response()->json([
            'data' => [
                'form_import' => $this->serialize($formImport->fresh()),
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
            ],
        ]);
    }

    public function source(Request $request, FormImport $formImport)
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        $disk = $formImport->source_disk ?: 'local';
        $path = (string) $formImport->source_path;
        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            abort(404, 'Arquivo fonte não encontrado.');
        }

        $mime = (string) ($formImport->source_mime ?: 'application/octet-stream');
        $name = (string) ($formImport->source_original_name ?: basename($path));

        return Storage::disk($disk)->response($path, $name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    public function destroy(Request $request, FormImport $formImport): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        if ($formImport->source_path) {
            Storage::disk($formImport->source_disk ?: 'local')->delete($formImport->source_path);
        }
        $formImport->pages()->delete();
        $formImport->delete();

        return response()->json(['data' => ['message' => 'Importação removida.']]);
    }

    private function assertOrg(Request $request, FormImport $formImport): void
    {
        $orgId = $this->currentOrganizationId($request);
        if (! $orgId || (int) $formImport->organization_id !== (int) $orgId) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(FormImport $import): array
    {
        $mime = strtolower((string) ($import->source_mime ?? ''));
        $sourceKind = 'file';
        if (str_starts_with($mime, 'image/')) {
            $sourceKind = 'image';
        } elseif ($mime === 'application/pdf' || str_ends_with(strtolower((string) $import->source_original_name), '.pdf')) {
            $sourceKind = 'pdf';
        } elseif (str_starts_with($mime, 'text/') || str_ends_with(strtolower((string) $import->source_original_name), '.txt')) {
            $sourceKind = 'text';
        }

        return [
            'id' => $import->id,
            'status' => $import->status,
            'provider' => $import->provider,
            'model_version' => $import->model_version,
            'source_original_name' => $import->source_original_name,
            'source_mime' => $import->source_mime,
            'source_size' => $import->source_size,
            'source_kind' => $sourceKind,
            'has_source' => filled($import->source_path),
            'extracted_schema' => $import->extracted_schema_json,
            'reviewed_schema' => $import->reviewed_schema_json,
            'warnings' => $import->warnings_json,
            'error_code' => $import->error_code,
            'error_message' => $import->error_message,
            'template_id' => $import->template_id,
            'processed_at' => $import->processed_at?->toIso8601String(),
            'committed_at' => $import->committed_at?->toIso8601String(),
            'created_at' => $import->created_at?->toIso8601String(),
            'retention_days' => $this->formImportConfig->getRetentionDays(),
            'source_purge_after' => $import->created_at
                ? $import->created_at->copy()->addDays($this->formImportConfig->getRetentionDays())->toIso8601String()
                : null,
        ];
    }
}
