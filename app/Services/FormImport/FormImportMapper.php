<?php

namespace App\Services\FormImport;

use App\Models\FormField;
use App\Models\FormImport;
use App\Models\FormTemplate;
use App\Services\TemplateVersionService;
use Illuminate\Support\Facades\DB;

class FormImportMapper
{
    public function __construct(
        private FormSchemaValidator $validator,
        private TemplateVersionService $versions,
    ) {}

    /**
     * Cria template + campos a partir do schema revisado. Não publica link público.
     */
    public function commit(FormImport $import, ?string $templateName = null): FormTemplate
    {
        $schema = $import->reviewed_schema_json ?? $import->extracted_schema_json ?? [];
        $validated = $this->validator->validate(is_array($schema) ? $schema : []);
        if (! $validated['ok']) {
            throw new \InvalidArgumentException(implode(' ', $validated['errors']));
        }

        return DB::transaction(function () use ($import, $validated, $templateName) {
            $name = trim((string) ($templateName ?: $import->source_original_name ?: 'Ficha importada'));
            $name = pathinfo($name, PATHINFO_FILENAME) ?: 'Ficha importada';

            $template = FormTemplate::create([
                'organization_id' => $import->organization_id,
                'name' => mb_substr($name, 0, 255),
                'description' => 'Modelo criado a partir de importação (revisão humana).',
                'category' => 'importado',
                'is_active' => true,
                'public_enabled' => false,
            ]);

            foreach ($validated['fields'] as $i => $f) {
                $structural = in_array($f['type'], ['heading', 'notice', 'section_break'], true);
                FormField::create([
                    'template_id' => $template->id,
                    'type' => $f['type'],
                    'label' => $f['label'],
                    'name_key' => $f['name_key'],
                    'required' => $structural ? false : (bool) $f['required'],
                    'options_json' => $f['options'] !== [] ? ['options' => $f['options']] : null,
                    'visibility_rules' => $f['visibility_rules'],
                    'sort_order' => $f['sort_order'] ?: ($i + 1),
                ]);
            }

            $this->versions->ensureSyncedVersion($template->fresh(['fields']));

            $import->update([
                'template_id' => $template->id,
                'status' => 'committed',
                'committed_at' => now(),
                'reviewed_schema_json' => ['fields' => $validated['fields']],
            ]);

            return $template->fresh(['fields']);
        });
    }
}
