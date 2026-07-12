<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;

class TemplateVersionService
{
    /**
     * Monta o snapshot atual dos campos do template (ordem estável).
     *
     * @return list<array<string, mixed>>
     */
    public function buildFieldsSnapshot(FormTemplate $template): array
    {
        $template->loadMissing('fields');

        return $template->fields
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($f) => [
                'id' => $f->id,
                'type' => $f->type,
                'label' => $f->label,
                'name_key' => $f->name_key,
                'required' => (bool) $f->required,
                'options_json' => $f->options_json,
                'visibility_rules' => $f->visibility_rules,
                'clinical_step_kind' => $f->clinical_step_kind,
                'sort_order' => (int) $f->sort_order,
            ])
            ->all();
    }

    /**
     * Cria uma nova versão do template com snapshot atual dos campos (para evidência e auditoria).
     */
    public function createVersionForTemplate(FormTemplate $template): FormTemplateVersion
    {
        $fieldsSnapshot = $this->buildFieldsSnapshot($template);
        $nextVersion = (int) FormTemplateVersion::where('form_template_id', $template->id)->max('version') + 1;

        return FormTemplateVersion::create([
            'form_template_id' => $template->id,
            'version' => max(1, $nextVersion),
            'name' => $template->name,
            'description' => $template->description,
            'fields_snapshot' => $fieldsSnapshot,
        ]);
    }

    /**
     * Garante que a última versão reflete o template atual.
     * Se campos/metadados mudaram desde a última versão, cria uma nova.
     */
    public function ensureSyncedVersion(FormTemplate $template): FormTemplateVersion
    {
        $currentSnapshot = $this->buildFieldsSnapshot($template);
        $latest = FormTemplateVersion::where('form_template_id', $template->id)
            ->orderByDesc('version')
            ->first();

        if (! $latest) {
            return $this->createVersionForTemplate($template);
        }

        $sameFields = $this->normalizeJson($latest->fields_snapshot) === $this->normalizeJson($currentSnapshot);
        $sameMeta = (string) $latest->name === (string) $template->name
            && (string) ($latest->description ?? '') === (string) ($template->description ?? '');

        if ($sameFields && $sameMeta) {
            return $latest;
        }

        return $this->createVersionForTemplate($template);
    }

    /**
     * Retorna a versão atual do template (sincronizada) ou cria a primeira.
     */
    public function getOrCreateCurrentVersion(FormTemplate $template): FormTemplateVersion
    {
        return $this->ensureSyncedVersion($template);
    }

    /**
     * Compara duas versões persistidas do template (governança antes de republicar).
     *
     * @return array{
     *   meta: array{name_changed: bool, description_changed: bool},
     *   fields: array{
     *     added: list<array<string, mixed>>,
     *     removed: list<array<string, mixed>>,
     *     changed: list<array<string, mixed>>,
     *     reordered: list<array<string, mixed>>
     *   },
     *   has_changes: bool
     * }
     */
    public function compareVersions(FormTemplateVersion $from, FormTemplateVersion $to): array
    {
        $meta = [
            'name_changed' => (string) $from->name !== (string) $to->name,
            'description_changed' => (string) ($from->description ?? '') !== (string) ($to->description ?? ''),
        ];

        $fromFields = $this->indexFieldsByKey($from->fields_snapshot ?? []);
        $toFields = $this->indexFieldsByKey($to->fields_snapshot ?? []);

        $added = [];
        $removed = [];
        $changed = [];
        $reordered = [];

        foreach ($toFields as $key => $field) {
            if (! isset($fromFields[$key])) {
                $added[] = $this->summarizeField($field);
            }
        }

        foreach ($fromFields as $key => $field) {
            if (! isset($toFields[$key])) {
                $removed[] = $this->summarizeField($field);
            }
        }

        foreach ($toFields as $key => $toField) {
            if (! isset($fromFields[$key])) {
                continue;
            }

            $fieldChanges = $this->diffFieldAttributes($fromFields[$key], $toField);
            if ($fieldChanges !== []) {
                $changed[] = [
                    'name_key' => $key,
                    'label' => (string) ($toField['label'] ?? $key),
                    'type' => (string) ($toField['type'] ?? ''),
                    'changes' => $fieldChanges,
                ];
            }
        }

        $fromOrder = array_values(array_intersect(array_keys($fromFields), array_keys($toFields)));
        $toOrder = array_values(array_intersect(array_keys($toFields), array_keys($fromFields)));
        if ($fromOrder !== $toOrder) {
            $reordered = [
                'from' => array_map(fn ($key) => $this->summarizeField($fromFields[$key]), $fromOrder),
                'to' => array_map(fn ($key) => $this->summarizeField($toFields[$key]), $toOrder),
            ];
        }

        $hasChanges = $meta['name_changed']
            || $meta['description_changed']
            || $added !== []
            || $removed !== []
            || $changed !== []
            || $reordered !== [];

        return [
            'meta' => $meta,
            'fields' => [
                'added' => $added,
                'removed' => $removed,
                'changed' => $changed,
                'reordered' => $reordered,
            ],
            'has_changes' => $hasChanges,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $snapshot
     * @return array<string, array<string, mixed>>
     */
    private function indexFieldsByKey(array $snapshot): array
    {
        $indexed = [];
        foreach ($snapshot as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = (string) ($field['name_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $indexed[$key] = $field;
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{name_key: string, label: string, type: string}
     */
    private function summarizeField(array $field): array
    {
        return [
            'name_key' => (string) ($field['name_key'] ?? ''),
            'label' => (string) ($field['label'] ?? ''),
            'type' => (string) ($field['type'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $to
     * @return list<string>
     */
    private function diffFieldAttributes(array $from, array $to): array
    {
        $changes = [];
        $attrs = ['type', 'label', 'required', 'options_json', 'visibility_rules', 'clinical_step_kind'];

        foreach ($attrs as $attr) {
            if ($this->normalizeJson($from[$attr] ?? null) !== $this->normalizeJson($to[$attr] ?? null)) {
                $changes[] = $attr;
            }
        }

        return $changes;
    }

    private function normalizeJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
