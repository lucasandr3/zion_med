<?php

declare(strict_types=1);

namespace App\Services\ClientDocumentPacks;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\Definitions\StefanieLinoDocumentPack;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Provisiona os 9 documentos da Dra. Stefanie Lino em uma organização existente.
 * Idempotente por nome do template; reanexa o PDF original.
 */
final class StefanieLinoDocumentProvisioner
{
    /**
     * @return array{created: int, updated: int, attached: int, skipped: int, items: list<string>}
     */
    public function provision(Organization $organization, ?User $createdBy = null, bool $dryRun = false): array
    {
        $assetsDir = database_path('seeders/assets/stefanie-lino');
        if (! is_dir($assetsDir)) {
            throw new \RuntimeException('Pasta de PDFs não encontrada: '.$assetsDir);
        }

        $created = 0;
        $updated = 0;
        $attached = 0;
        $skipped = 0;
        $items = [];

        foreach (StefanieLinoDocumentPack::documents() as $doc) {
            $extra = $doc['extra_fields'] ?? [];
            $fields = array_values(array_merge($doc['fields'] ?? [], $extra));
            $name = (string) $doc['name'];
            $pdfName = (string) ($doc['source_pdf'] ?? '');
            $pdfPath = $assetsDir.DIRECTORY_SEPARATOR.$pdfName;

            if ($pdfName === '' || ! is_file($pdfPath)) {
                $skipped++;
                $items[] = "SKIP {$name} (PDF ausente: {$pdfName})";

                continue;
            }

            $template = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('name', $name)
                ->first();

            if ($dryRun) {
                $action = $template ? 'update+attach' : 'create+attach';
                $items[] = "DRY-RUN {$action}: {$name}";
                if ($template) {
                    $updated++;
                } else {
                    $created++;
                }
                $attached++;

                continue;
            }

            if ($template === null) {
                $template = FormTemplate::withoutGlobalScopes()->create([
                    'organization_id' => $organization->id,
                    'name' => $name,
                    'description' => $doc['description'] ?? null,
                    'category' => $doc['category'] ?? null,
                    'document_kind' => $doc['document_kind'] ?? 'ficha',
                    'is_active' => true,
                    'public_enabled' => false,
                    'public_require_person_link' => false,
                    'created_by' => $createdBy?->id,
                ]);

                foreach ($fields as $order => $field) {
                    $opts = $field['options'] ?? null;
                    unset($field['options']);
                    FormField::query()->create([
                        ...$field,
                        'template_id' => $template->id,
                        'sort_order' => $field['sort_order'] ?? ($order + 1),
                        'options_json' => $opts ? ['options' => $opts] : null,
                    ]);
                }
                $created++;
            } else {
                $template->update([
                    'description' => $doc['description'] ?? $template->description,
                    'category' => $doc['category'] ?? $template->category,
                    'document_kind' => $doc['document_kind'] ?? $template->document_kind,
                    'is_active' => true,
                ]);
                $updated++;
            }

            $this->attachSourcePdf($template->fresh(), $pdfPath, $pdfName);
            $attached++;
            $items[] = "OK {$name}";
        }

        return compact('created', 'updated', 'attached', 'skipped', 'items');
    }

    private function attachSourcePdf(FormTemplate $template, string $absolutePdfPath, string $originalName): void
    {
        $organizationId = (int) $template->organization_id;
        $diskName = $this->resolveStorageDisk();
        $directory = 'organizations/'.$organizationId.'/templates/'.$template->id.'/source';
        $targetRelative = $directory.'/'.$originalName;

        if ($template->source_document_path) {
            try {
                Storage::disk((string) ($template->source_document_disk ?: $diskName))
                    ->delete((string) $template->source_document_path);
            } catch (\Throwable) {
            }
        }

        try {
            Storage::disk($diskName)->makeDirectory($directory);
        } catch (\Throwable) {
            // S3/MinIO não precisa de mkdir explícito.
        }

        Storage::disk($diskName)->put($targetRelative, File::get($absolutePdfPath));

        $template->update([
            'source_document_disk' => $diskName,
            'source_document_path' => $targetRelative,
            'source_document_original_name' => $originalName,
            'source_document_mime' => 'application/pdf',
            'source_document_size' => File::size($absolutePdfPath) ?: null,
        ]);
    }

    private function resolveStorageDisk(): string
    {
        $minio = config('filesystems.disks.minio_assets');
        $key = is_array($minio) ? trim((string) ($minio['key'] ?? '')) : '';
        $endpoint = is_array($minio) ? trim((string) ($minio['endpoint'] ?? '')) : '';

        if ($key !== '' && $endpoint !== '') {
            return 'minio_assets';
        }

        return 'local';
    }
}
