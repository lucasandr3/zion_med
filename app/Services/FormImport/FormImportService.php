<?php

namespace App\Services\FormImport;

use App\Jobs\ProcessFormImportJob;
use App\Models\FormImport;
use App\Models\FormImportPage;
use App\Models\FormTemplate;
use App\Services\ProductEventService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormImportService
{
    public function __construct(
        private FormSchemaValidator $validator,
        private FormImportMapper $mapper,
        private ProductEventService $productEvents,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function createFromUpload(
        int $organizationId,
        int $userId,
        array $files,
        ?string $idempotencyKey = null,
    ): FormImport {
        $files = array_values(array_filter($files, static fn ($f) => $f instanceof UploadedFile));
        if ($files === []) {
            throw new \InvalidArgumentException('Envie ao menos um arquivo.');
        }

        if ($idempotencyKey) {
            $existing = FormImport::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $disk = (string) config('form_import.disk', 'local');
        $dir = 'form-imports/'.$organizationId.'/'.now()->format('Y/m');
        $first = $files[0];
        $storedPaths = [];

        try {
            foreach ($files as $index => $file) {
                $storedPaths[] = $file->storeAs(
                    $dir,
                    Str::uuid()->toString().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                    $disk
                );
            }
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                try {
                    Storage::disk($disk)->delete($path);
                } catch (\Throwable) {
                }
            }
            throw $e;
        }

        $names = array_map(static fn (UploadedFile $f) => $f->getClientOriginalName(), $files);
        $displayName = count($names) === 1
            ? $names[0]
            : $names[0].' (+'.(count($names) - 1).' arquivo'.(count($names) > 2 ? 's' : '').')';

        $import = FormImport::query()->create([
            'organization_id' => $organizationId,
            'created_by' => $userId,
            'status' => 'queued',
            'provider' => (string) config('form_import.provider', 'openai'),
            'source_disk' => $disk,
            'source_path' => $storedPaths[0],
            'source_original_name' => $displayName,
            'source_mime' => $first->getMimeType(),
            'source_size' => array_sum(array_map(static fn (UploadedFile $f) => (int) $f->getSize(), $files)),
            'idempotency_key' => $idempotencyKey,
        ]);

        foreach ($files as $index => $file) {
            FormImportPage::query()->create([
                'form_import_id' => $import->id,
                'page_number' => $index + 1,
                'path' => $storedPaths[$index],
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->productEvents->record(
            'template_import_started',
            $organizationId,
            $userId,
            ['form_import_id' => $import->id, 'page_count' => count($files)],
            'template_import_started:import:'.$import->id,
            ['channel' => 'app']
        );

        ProcessFormImportJob::dispatch($import->id);

        // PoC: se a fila não consumiu imediatamente, processa na request (ex.: queue=database sem worker).
        $import->refresh();
        if ($import->status === 'queued') {
            $this->process($import);
            $import->refresh();
        }

        return $import;
    }

    public function process(FormImport $import): void
    {
        $import->update(['status' => 'processing', 'error_code' => null, 'error_message' => null]);

        try {
            $disk = $import->source_disk ?: 'local';
            $sources = $this->resolveSources($import, $disk);
            if ($sources === []) {
                throw new \RuntimeException('Arquivo fonte ausente no storage.');
            }

            $provider = $this->resolveProvider((string) ($import->provider ?: config('form_import.provider')));
            $result = count($sources) === 1
                ? $provider->extract($sources[0]['path'], $sources[0]['mime'], $sources[0]['name'])
                : $provider->extractMany($sources);
            $validated = $this->validator->validate(['fields' => $result['fields'] ?? []]);

            $warnings = array_merge($result['warnings'] ?? [], array_map(
                static fn ($w) => ['code' => 'schema_warning', 'level' => 'warning', 'message' => $w],
                $validated['warnings']
            ));

            if (! $validated['ok']) {
                $import->update([
                    'status' => 'failed',
                    'error_code' => 'schema_invalid',
                    'error_message' => implode(' ', $validated['errors']),
                    'warnings_json' => $warnings,
                    'processed_at' => now(),
                ]);

                return;
            }

            $import->update([
                'status' => 'completed',
                'provider' => $result['provider'] ?? $import->provider,
                'model_version' => $result['model_version'] ?? null,
                'extracted_schema_json' => ['fields' => $validated['fields']],
                'reviewed_schema_json' => ['fields' => $validated['fields']],
                'warnings_json' => $warnings,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_code' => 'processing_failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'processed_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function saveDraft(FormImport $import, array $schema): FormImport
    {
        $validated = $this->validator->validate($schema);
        if (! $validated['ok']) {
            throw new \InvalidArgumentException(implode(' ', $validated['errors']));
        }
        $import->update([
            'reviewed_schema_json' => ['fields' => $validated['fields']],
        ]);

        return $import->fresh();
    }

    public function commit(FormImport $import, ?string $templateName = null): FormTemplate
    {
        if (! in_array($import->status, ['completed', 'committed'], true)) {
            throw new \InvalidArgumentException('A importação ainda não está pronta para commit.');
        }

        return $this->mapper->commit($import, $templateName);
    }

    /**
     * @return list<array{path: string, mime: string, name: ?string, page: int}>
     */
    private function resolveSources(FormImport $import, string $disk): array
    {
        $pages = $import->pages()->orderBy('page_number')->get();
        if ($pages->isNotEmpty()) {
            $sources = [];
            foreach ($pages as $page) {
                $relative = (string) $page->path;
                if ($relative === '' || ! Storage::disk($disk)->exists($relative)) {
                    continue;
                }
                $sources[] = [
                    'path' => Storage::disk($disk)->path($relative),
                    'mime' => (string) ($page->mime ?: $import->source_mime ?: 'application/octet-stream'),
                    'name' => $page->original_name ?: $import->source_original_name,
                    'page' => (int) $page->page_number,
                ];
            }

            return $sources;
        }

        $relative = (string) $import->source_path;
        if ($relative === '' || ! Storage::disk($disk)->exists($relative)) {
            return [];
        }

        return [[
            'path' => Storage::disk($disk)->path($relative),
            'mime' => (string) ($import->source_mime ?: 'application/octet-stream'),
            'name' => $import->source_original_name,
            'page' => 1,
        ]];
    }

    private function resolveProvider(string $name): DocumentExtractionProvider
    {
        $name = strtolower(trim($name));
        if ($name === 'openai' || ($name === '' && filled(config('form_import.openai_api_key')))) {
            return app(OpenAiExtractionProvider::class);
        }

        return app(HeuristicExtractionProvider::class);
    }
}
