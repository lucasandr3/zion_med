<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormTemplate;

class FieldVisibilityService
{
    /**
     * @param  array<string, mixed>|null  $visibilityRules
     * @param  array<string, mixed>  $values
     */
    public function isVisible(?array $visibilityRules, array $values): bool
    {
        if ($visibilityRules === null || $visibilityRules === []) {
            return true;
        }

        $conditions = $visibilityRules['show_when'] ?? [];
        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition) || ! $this->matchesCondition($condition, $values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $values
     */
    public function matchesCondition(array $condition, array $values): bool
    {
        $field = trim((string) ($condition['field'] ?? ''));
        if ($field === '') {
            return true;
        }

        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? '';
        $actual = $values[$field] ?? null;

        return match ($operator) {
            'equals' => $this->normalizeValue($actual) === $this->normalizeValue($expected),
            'not_equals' => $this->normalizeValue($actual) !== $this->normalizeValue($expected),
            'filled' => $this->isFilled($actual),
            'empty' => ! $this->isFilled($actual),
            default => $this->normalizeValue($actual) === $this->normalizeValue($expected),
        };
    }

    /**
     * @param  iterable<FormField>  $fields
     * @param  array<string, mixed>  $values
     * @return list<FormField>
     */
    public function visibleFields(iterable $fields, array $values): array
    {
        $visible = [];
        foreach ($fields as $field) {
            if ($this->isVisible($field->visibility_rules, $values)) {
                $visible[] = $field;
            }
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function shouldShowActors(FormTemplate $template, array $values): bool
    {
        $documentKind = $template->document_kind
            ?: ($template->category === 'consentimento' ? 'consentimento' : 'ficha');
        if ($documentKind !== 'consentimento') {
            return false;
        }

        $rules = $template->actors_visibility_rules;
        if ($rules === null || $rules === []) {
            return true;
        }

        return $this->isVisible($rules, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function requiresGuardian(FormTemplate $template, array $values): bool
    {
        if (! $this->shouldShowActors($template, $values)) {
            return false;
        }

        $rules = $template->actors_visibility_rules;
        if (! is_array($rules) || $rules === []) {
            return false;
        }

        return (bool) ($rules['require_guardian'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function buildSubmitValues(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if (! is_string($key) || str_starts_with($key, '_')) {
                continue;
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 'true';
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 'false';
        }
        if ($value === null) {
            return '';
        }

        return mb_strtolower(trim((string) $value));
    }

    private function isFilled(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0' || $value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }
}
