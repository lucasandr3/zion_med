<?php

namespace App\Services\FormImport;

class FormSchemaValidator
{
    private const ALLOWED_TYPES = [
        'text', 'textarea', 'number', 'date', 'select', 'checkbox', 'radio',
        'file', 'signature', 'heading', 'notice', 'section_break',
    ];

    /**
     * @param  array<string, mixed>  $schema
     * @return array{ok: bool, errors: list<string>, warnings: list<string>, fields: list<array<string, mixed>>}
     */
    public function validate(array $schema): array
    {
        $errors = [];
        $warnings = [];
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields) || $fields === []) {
            return ['ok' => false, 'errors' => ['Schema sem campos.'], 'warnings' => [], 'fields' => []];
        }

        $normalized = [];
        $keys = [];
        foreach (array_values($fields) as $i => $raw) {
            if (! is_array($raw)) {
                $errors[] = "Campo #{$i} inválido.";
                continue;
            }
            $type = (string) ($raw['type'] ?? 'text');
            if (! in_array($type, self::ALLOWED_TYPES, true)) {
                $warnings[] = "Tipo '{$type}' no campo #{$i} mapeado para text.";
                $type = 'text';
            }
            $label = trim((string) ($raw['label'] ?? ''));
            if ($label === '') {
                $errors[] = "Campo #{$i} sem rótulo.";
                continue;
            }
            $key = (string) ($raw['name_key'] ?? '');
            $key = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '');
            $key = trim($key, '_');
            if ($key === '') {
                $key = 'campo_'.($i + 1);
            }
            if (isset($keys[$key])) {
                $key = $key.'_'.($i + 1);
            }
            $keys[$key] = true;

            $options = [];
            if (isset($raw['options']) && is_array($raw['options'])) {
                $options = array_values(array_filter(array_map(static fn ($o) => trim((string) $o), $raw['options'])));
            }
            if (in_array($type, ['radio', 'select', 'checkbox'], true) && $options === []) {
                $warnings[] = "Campo {$key} ({$type}) sem opções.";
            }

            $normalized[] = [
                'type' => $type,
                'label' => mb_substr($label, 0, 2000),
                'name_key' => mb_substr($key, 0, 80),
                'required' => (bool) ($raw['required'] ?? false),
                'options' => $options,
                'visibility_rules' => is_array($raw['visibility_rules'] ?? null) ? $raw['visibility_rules'] : null,
                'sort_order' => (int) ($raw['sort_order'] ?? ($i + 1)),
                'page' => (int) ($raw['page'] ?? 1),
                'source_text' => isset($raw['source_text']) ? mb_substr((string) $raw['source_text'], 0, 500) : null,
            ];
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'fields' => $normalized,
        ];
    }
}
