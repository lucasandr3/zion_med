<?php

namespace App\Services\FormImport;

use App\Jobs\ProcessFormImportJob;
use App\Models\FormImport;
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

    public function createFromUpload(
        int $organizationId,
        int $userId,
        UploadedFile $file,
        ?string $idempotencyKey = null,
    ): FormImport {
        if ($idempotencyKey) {
            $existing = FormImport::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $disk = (string) config('form_import.disk', 'local');
        $dir = 'form-imports/'.$organizationId.'/'.now()->format('Y/m');
        $path = $file->storeAs($dir, Str::uuid()->toString().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(), $disk);

        $import = FormImport::query()->create([
            'organization_id' => $organizationId,
            'created_by' => $userId,
            'status' => 'queued',
            'provider' => (string) config('form_import.provider', 'openai'),
            'source_disk' => $disk,
            'source_path' => $path,
            'source_original_name' => $file->getClientOriginalName(),
            'source_mime' => $file->getMimeType(),
            'source_size' => $file->getSize(),
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->productEvents->record(
            'template_import_started',
            $organizationId,
            $userId,
            ['form_import_id' => $import->id],
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
            $relative = (string) $import->source_path;
            if (! Storage::disk($disk)->exists($relative)) {
                throw new \RuntimeException('Arquivo fonte ausente no storage.');
            }
            $absolute = Storage::disk($disk)->path($relative);
            $provider = $this->resolveProvider((string) ($import->provider ?: config('form_import.provider')));
            $result = $provider->extract($absolute, (string) $import->source_mime, $import->source_original_name);
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

    private function resolveProvider(string $name): DocumentExtractionProvider
    {
        $name = strtolower(trim($name));
        if ($name === 'openai' || ($name === '' && filled(config('form_import.openai_api_key')))) {
            return app(OpenAiExtractionProvider::class);
        }

        return app(HeuristicExtractionProvider::class);
    }
}
