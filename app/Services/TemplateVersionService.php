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

    private function normalizeJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
