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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $maxFiles = max(1, (int) config('form_import.max_files', 10));

        $request->validate([
            'file' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:pdf,jpg,jpeg,png,txt'],
            'files' => ['nullable', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['file', 'max:'.$maxKb, 'mimes:pdf,jpg,jpeg,png,txt'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
            'consent_blank_ack' => ['accepted'],
        ], [
            'consent_blank_ack.accepted' => 'Confirme que o arquivo é uma ficha em branco, sem dados de paciente.',
            'files.max' => 'Envie no máximo '.$maxFiles.' arquivos por importação.',
        ]);

        $files = $this->resolveUploadFiles($request);
        if ($files === []) {
            throw ValidationException::withMessages([
                'file' => 'Envie um PDF, foto(s) ou TXT da ficha em branco.',
            ]);
        }

        $this->assertCompatibleFileSet($files);

        try {
            $import = $this->imports->createFromUpload(
                (int) $orgId,
                (int) $request->user()->id,
                $files,
                $request->input('idempotency_key'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->serialize($import->fresh(['pages']))], 201);
    }

    public function show(Request $request, FormImport $formImport): JsonResponse
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        return response()->json(['data' => $this->serialize($formImport->loadMissing('pages'))]);
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

        return response()->json(['data' => $this->serialize($import->loadMissing('pages'))]);
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
                'form_import' => $this->serialize($formImport->fresh(['pages'])),
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'has_source_document' => $template->hasSourceDocument(),
                    'source_document_original_name' => $template->source_document_original_name,
                ],
            ],
        ]);
    }

    public function source(Request $request, FormImport $formImport)
    {
        $this->authorize('manage-templates');
        $this->assertOrg($request, $formImport);

        $disk = $formImport->source_disk ?: 'local';
        $pageNumber = max(1, (int) $request->query('page', 1));
        $page = $formImport->pages()->where('page_number', $pageNumber)->first();

        if ($page && filled($page->path)) {
            $path = (string) $page->path;
            $mime = (string) ($page->mime ?: 'application/octet-stream');
            $name = (string) ($page->original_name ?: basename($path));
        } else {
            $path = (string) $formImport->source_path;
            $mime = (string) ($formImport->source_mime ?: 'application/octet-stream');
            $name = (string) ($formImport->source_original_name ?: basename($path));
        }

        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            abort(404, 'Arquivo fonte não encontrado.');
        }

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

        $disk = $formImport->source_disk ?: 'local';
        foreach ($formImport->pages as $page) {
            if ($page->path) {
                try {
                    Storage::disk($disk)->delete($page->path);
                } catch (\Throwable) {
                }
            }
        }
        if ($formImport->source_path) {
            try {
                Storage::disk($disk)->delete($formImport->source_path);
            } catch (\Throwable) {
            }
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
     * @return list<UploadedFile>
     */
    private function resolveUploadFiles(Request $request): array
    {
        $files = [];
        if ($request->hasFile('files')) {
            $uploaded = $request->file('files');
            if (is_array($uploaded)) {
                foreach ($uploaded as $file) {
                    if ($file instanceof UploadedFile) {
                        $files[] = $file;
                    }
                }
            } elseif ($uploaded instanceof UploadedFile) {
                $files[] = $uploaded;
            }
        }

        if ($files === [] && $request->hasFile('file')) {
            $single = $request->file('file');
            if ($single instanceof UploadedFile) {
                $files[] = $single;
            }
        }

        return $files;
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function assertCompatibleFileSet(array $files): void
    {
        if (count($files) <= 1) {
            return;
        }

        foreach ($files as $file) {
            $mime = strtolower((string) $file->getMimeType());
            $name = strtolower($file->getClientOriginalName());
            $isImage = str_starts_with($mime, 'image/') || preg_match('/\.(jpe?g|png)$/', $name);
            if (! $isImage) {
                throw ValidationException::withMessages([
                    'files' => 'Para documento em várias páginas, envie só fotos (JPG/PNG). PDF deve ser um único arquivo.',
                ]);
            }
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

        $pages = $import->relationLoaded('pages')
            ? $import->pages
            : $import->pages()->orderBy('page_number')->get();

        return [
            'id' => $import->id,
            'status' => $import->status,
            'provider' => $import->provider,
            'model_version' => $import->model_version,
            'source_original_name' => $import->source_original_name,
            'source_mime' => $import->source_mime,
            'source_size' => $import->source_size,
            'source_kind' => $sourceKind,
            'has_source' => filled($import->source_path) || $pages->isNotEmpty(),
            'source_page_count' => max(1, $pages->count() ?: (filled($import->source_path) ? 1 : 0)),
            'pages' => $pages->map(static fn ($p) => [
                'page_number' => (int) $p->page_number,
                'original_name' => $p->original_name,
                'mime' => $p->mime,
                'size' => $p->size,
            ])->values()->all(),
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
