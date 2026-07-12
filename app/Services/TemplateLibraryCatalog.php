<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Services\ClinicalStepStructureService;
use Database\Seeders\FormTemplateDefinitions;
use Illuminate\Support\Str;

class TemplateLibraryCatalog
{
    public const CONTENT_VERSION = 2;

    /** @var list<string> */
    private const NICHE_KEYS = [
        'geral',
        'clinica_medica',
        'odontologia',
        'estetica',
        'fisioterapia',
        'psicologia',
        'pediatria',
        'ginecologia',
        'oftalmologia',
        'dermatologia',
        'laboratorio',
        'veterinaria',
    ];

    /** @var array<string, array{legal: string, clinical: string, reviewed_at: string|null}> */
    private const APPROVED_NAME_FRAGMENTS = [
        'estetica' => ['tcle', 'autorização de imagem', 'autorizacao de imagem'],
        'odontologia' => ['tcle', 'termo de consentimento', 'plano de tratamento'],
        'veterinaria' => ['internação e tratamento', 'internacao e tratamento', 'cirurgia veterinária', 'cirurgia veterinaria'],
        'geral' => ['termo de consentimento (atendimento', 'termo de telemedicina'],
    ];

    public static function makeLibraryKey(string $niche, string $name): string
    {
        return strtolower($niche).'__'.Str::slug($name, '_');
    }

    /**
     * @return array{meta: array<string, mixed>, specialties: list<array<string, mixed>>}
     */
    public function catalog(?string $preferredNiche = null, ?string $categoryFilter = null, ?int $organizationId = null): array
    {
        $preferredNiche = $this->normalizeNiche($preferredNiche);
        $items = $this->allItems();
        $installed = $organizationId ? $this->installedKeysForOrganization($organizationId) : [];

        $filtered = array_values(array_filter($items, function (array $item) use ($preferredNiche, $categoryFilter): bool {
            if ($categoryFilter !== null && $categoryFilter !== '' && ($item['category'] ?? '') !== $categoryFilter) {
                return false;
            }
            if ($preferredNiche === 'geral') {
                return true;
            }

            return ($item['specialty'] ?? '') === $preferredNiche || ($item['specialty'] ?? '') === 'geral';
        }));

        foreach ($filtered as &$item) {
            $key = $item['library_key'];
            $item['installed_template_id'] = $installed[$key] ?? null;
            unset($item['fields']);
        }
        unset($item);

        $grouped = [];
        foreach ($filtered as $item) {
            $spec = $item['specialty'];
            if (! isset($grouped[$spec])) {
                $grouped[$spec] = [
                    'key' => $spec,
                    'label' => FormTemplate::categoryLabels()[$spec] ?? $spec,
                    'templates' => [],
                ];
            }
            $grouped[$spec]['templates'][] = $item;
        }

        usort($grouped, function (array $a, array $b) use ($preferredNiche): int {
            if ($a['key'] === $preferredNiche) {
                return -1;
            }
            if ($b['key'] === $preferredNiche) {
                return 1;
            }

            return strcmp($a['label'], $b['label']);
        });

        $approvedCount = count(array_filter($filtered, fn (array $i): bool => ($i['legal_review_status'] ?? '') === 'approved'
            && ($i['clinical_review_status'] ?? '') === 'approved'));

        return [
            'meta' => [
                'total' => count($filtered),
                'niche' => $preferredNiche,
                'approved_count' => $approvedCount,
                'content_version' => self::CONTENT_VERSION,
            ],
            'specialties' => array_values($grouped),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByKey(string $libraryKey): ?array
    {
        $libraryKey = strtolower(trim($libraryKey));
        foreach ($this->allItems() as $item) {
            if ($item['library_key'] === $libraryKey) {
                return $item;
            }
        }

        return null;
    }

    public function install(Organization $organization, string $libraryKey, ?int $userId = null): FormTemplate
    {
        $definition = $this->findByKey($libraryKey);
        if ($definition === null) {
            throw new \InvalidArgumentException('Modelo da biblioteca não encontrado.');
        }

        $existing = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('library_key', $libraryKey)
            ->first();
        if ($existing instanceof FormTemplate) {
            return $existing->load('fields');
        }

        $review = [
            'legal' => $definition['legal_review_status'],
            'clinical' => $definition['clinical_review_status'],
            'reviewed_at' => $definition['reviewed_at'],
        ];

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'category' => $definition['category'],
            'document_kind' => $definition['document_kind'],
            'library_key' => $libraryKey,
            'library_content_version' => $definition['content_version'],
            'legal_review_status' => $review['legal'],
            'clinical_review_status' => $review['clinical'],
            'library_reviewed_at' => $review['reviewed_at'],
            'is_active' => true,
            'public_enabled' => false,
            'public_require_person_link' => false,
            'created_by' => $userId,
        ]);

        foreach ($definition['fields'] as $fieldDef) {
            $opts = $fieldDef['options'] ?? null;
            unset($fieldDef['options']);
            FormField::create([
                'template_id' => $template->id,
                'type' => $fieldDef['type'],
                'label' => $fieldDef['label'],
                'name_key' => $fieldDef['name_key'],
                'required' => $fieldDef['required'] ?? false,
                'sort_order' => $fieldDef['sort_order'],
                'options_json' => $opts ? ['options' => $opts] : null,
                'clinical_step_kind' => $fieldDef['clinical_step_kind'] ?? null,
            ]);
        }

        if (($definition['document_kind'] ?? '') === 'consentimento'
            || str_contains(strtolower($definition['name'] ?? ''), 'tcle')) {
            app(ClinicalStepStructureService::class)->applyTcleStructure($template->fresh(['fields']));
        }

        return $template->fresh(['fields']);
    }

    /**
     * Resolve a definição de biblioteca para um template de consentimento já instalado.
     *
     * @return array<string, mixed>|null
     */
    public function resolveConsentDefinitionForTemplate(FormTemplate $template): ?array
    {
        if (is_string($template->library_key) && $template->library_key !== '') {
            $byKey = $this->findByKey($template->library_key);
            if ($byKey !== null && (($byKey['document_kind'] ?? '') === 'consentimento' || $this->looksLikeConsent((string) $byKey['name']))) {
                return $byKey;
            }
        }

        $name = trim((string) $template->name);
        $candidates = [];
        foreach ($this->allItems() as $item) {
            if (($item['document_kind'] ?? '') !== 'consentimento' && ! $this->looksLikeConsent((string) ($item['name'] ?? ''))) {
                continue;
            }
            if (strcasecmp((string) $item['name'], $name) === 0) {
                return $item;
            }
            $candidates[] = $item;
        }

        // Fallback por slug do nome (ex.: TCLE antigo vs novo texto de description).
        $slug = Str::slug($name, '_');
        foreach ($candidates as $item) {
            $itemSlug = Str::slug((string) $item['name'], '_');
            if ($itemSlug === $slug || str_contains((string) $item['library_key'], $slug)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Atualiza TCLE instalado: merge (padrão) ou replace dos campos a partir da biblioteca CFM.
     *
     * @return array{status: string, template_id: int, mode: string, fields_created: int, fields_updated: int, fields_removed: int, message: string}
     */
    public function upgradeConsentTemplate(FormTemplate $template, string $mode = 'merge', bool $dryRun = false): array
    {
        $mode = in_array($mode, ['merge', 'replace', 'scaffold'], true) ? $mode : 'merge';
        $definition = $this->resolveConsentDefinitionForTemplate($template);

        $base = [
            'status' => 'skipped',
            'template_id' => (int) $template->id,
            'mode' => $mode,
            'fields_created' => 0,
            'fields_updated' => 0,
            'fields_removed' => 0,
            'message' => '',
        ];

        if ($definition === null && $mode !== 'scaffold') {
            $base['message'] = 'Sem definição de biblioteca correspondente; use --mode=scaffold.';

            return $base;
        }

        if ($mode === 'scaffold') {
            if ($dryRun) {
                $base['status'] = 'would_scaffold';
                $base['message'] = 'Aplicaria notices-base CFM e etapas clínicas.';

                return $base;
            }

            $template->update([
                'document_kind' => 'consentimento',
                'category' => $template->category === 'cadastro_documentacao' ? 'consentimento' : ($template->category ?: 'consentimento'),
            ]);
            app(ClinicalStepStructureService::class)->applyTcleStructure($template->fresh(['fields']));
            app(TemplateVersionService::class)->ensureSyncedVersion($template->fresh(['fields']));
            $base['status'] = 'upgraded';
            $base['message'] = 'Scaffold CFM / etapas clínicas aplicado.';

            return $base;
        }

        /** @var array<string, mixed> $definition */
        $currentVersion = (int) ($template->library_content_version ?? 0);
        $targetVersion = (int) ($definition['content_version'] ?? self::CONTENT_VERSION);

        if ($mode === 'merge' && $currentVersion >= $targetVersion && $template->library_key === ($definition['library_key'] ?? null)) {
            // Ainda assim pode faltar disclosure — validar e scaffold se necessário.
            $issues = app(ClinicalStepValidationService::class)->validateForPublish($template->load('fields'));
            $blocking = array_filter(
                $issues,
                fn ($i) => in_array($i['code'], app(ClinicalStepValidationService::class)->blockingCodes(), true)
            );
            if ($blocking === []) {
                $base['status'] = 'up_to_date';
                $base['message'] = 'Já na versão '.$targetVersion.' e estruturalmente ok.';

                return $base;
            }
        }

        if ($dryRun) {
            $existingKeys = $template->fields()->pluck('name_key')->all();
            $defFields = $definition['fields'] ?? [];
            $created = 0;
            $updated = 0;
            $removed = 0;
            if ($mode === 'replace') {
                $removed = count($existingKeys);
                $created = count($defFields);
            } else {
                $defKeys = collect($defFields)->pluck('name_key')->all();
                foreach ($defFields as $fieldDef) {
                    if (in_array($fieldDef['name_key'], $existingKeys, true)) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
            }
            $base['status'] = 'would_upgrade';
            $base['fields_created'] = $created;
            $base['fields_updated'] = $updated;
            $base['fields_removed'] = $removed;
            $base['message'] = sprintf(
                'Simulação %s -> biblioteca %s (v%d).',
                $mode,
                $definition['library_key'] ?? '?',
                $targetVersion
            );

            return $base;
        }

        $created = 0;
        $updated = 0;
        $removed = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $template,
            $definition,
            $mode,
            $targetVersion,
            &$created,
            &$updated,
            &$removed
        ): void {
            $template->update([
                'name' => $definition['name'] ?? $template->name,
                'description' => $definition['description'] ?? $template->description,
                'category' => 'consentimento',
                'document_kind' => 'consentimento',
                'library_key' => $definition['library_key'] ?? $template->library_key,
                'library_content_version' => $targetVersion,
                'legal_review_status' => $definition['legal_review_status'] ?? $template->legal_review_status,
                'clinical_review_status' => $definition['clinical_review_status'] ?? $template->clinical_review_status,
                'library_reviewed_at' => $definition['reviewed_at'] ?? $template->library_reviewed_at,
                'uses_clinical_steps' => true,
            ]);

            $defFields = $definition['fields'] ?? [];

            if ($mode === 'replace') {
                $removed = $template->fields()->count();
                $template->fields()->delete();
                foreach ($defFields as $fieldDef) {
                    $this->createFieldFromDefinition($template, $fieldDef);
                    $created++;
                }
            } else {
                $existingByKey = $template->fields()->get()->keyBy('name_key');
                $maxOrder = (int) $template->fields()->max('sort_order');
                foreach ($defFields as $fieldDef) {
                    $key = (string) ($fieldDef['name_key'] ?? '');
                    if ($key === '') {
                        continue;
                    }
                    /** @var FormField|null $current */
                    $current = $existingByKey->get($key);
                    if (! $current) {
                        $fieldDef['sort_order'] = $fieldDef['sort_order'] ?? (++$maxOrder);
                        $this->createFieldFromDefinition($template, $fieldDef);
                        $created++;
                        continue;
                    }

                    $opts = $fieldDef['options'] ?? null;
                    $optionsJson = $opts ? ['options' => $opts] : $current->options_json;
                    $current->update([
                        'type' => $fieldDef['type'] ?? $current->type,
                        'label' => $fieldDef['label'] ?? $current->label,
                        'required' => (bool) ($fieldDef['required'] ?? $current->required),
                        'sort_order' => (int) ($fieldDef['sort_order'] ?? $current->sort_order),
                        'options_json' => $optionsJson,
                        'clinical_step_kind' => $fieldDef['clinical_step_kind'] ?? $current->clinical_step_kind,
                    ]);
                    $updated++;
                }
            }
        });

        app(ClinicalStepStructureService::class)->applyTcleStructure($template->fresh(['fields']));
        app(TemplateVersionService::class)->ensureSyncedVersion($template->fresh(['fields']));

        $base['status'] = 'upgraded';
        $base['fields_created'] = $created;
        $base['fields_updated'] = $updated;
        $base['fields_removed'] = $removed;
        $base['message'] = sprintf('Atualizado (%s) para v%d.', $mode, $targetVersion);

        return $base;
    }

    /**
     * @param  array<string, mixed>  $fieldDef
     */
    private function createFieldFromDefinition(FormTemplate $template, array $fieldDef): FormField
    {
        $opts = $fieldDef['options'] ?? null;

        return FormField::create([
            'template_id' => $template->id,
            'type' => $fieldDef['type'],
            'label' => $fieldDef['label'],
            'name_key' => $fieldDef['name_key'],
            'required' => $fieldDef['required'] ?? false,
            'sort_order' => $fieldDef['sort_order'] ?? 0,
            'options_json' => $opts ? ['options' => $opts] : null,
            'clinical_step_kind' => $fieldDef['clinical_step_kind'] ?? null,
        ]);
    }

    /**
     * @return list<string>
     */
    public function consentLibraryKeys(): array
    {
        $keys = [];
        foreach ($this->allItems() as $item) {
            if (($item['document_kind'] ?? '') === 'consentimento' || $this->looksLikeConsent((string) ($item['name'] ?? ''))) {
                // Evita termo de imagem/LGPD puro se não for TCLE clínico
                $name = strtolower((string) ($item['name'] ?? ''));
                if (str_contains($name, 'imagem') && ! str_contains($name, 'tcle') && ! str_contains($name, 'consentimento livre')) {
                    continue;
                }
                if (str_contains($name, 'termo de uso de dados') || str_contains($name, 'ciência lgpd') || str_contains($name, 'ciencia lgpd')) {
                    continue;
                }
                $keys[] = (string) $item['library_key'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, int>
     */
    private function installedKeysForOrganization(int $organizationId): array
    {
        return FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('library_key')
            ->pluck('id', 'library_key')
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allItems(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $items = [];
        foreach (self::NICHE_KEYS as $niche) {
            foreach (FormTemplateDefinitions::forNiche($niche) as $definition) {
                $key = self::makeLibraryKey($niche, $definition['name']);
                if (isset($items[$key])) {
                    continue;
                }
                $items[$key] = $this->enrichDefinition($definition, $niche);
            }
        }

        $cache = array_values($items);

        return $cache;
    }

    /**
     * @param  array{name: string, description: string, category: string, fields: array}  $definition
     * @return array<string, mixed>
     */
    private function enrichDefinition(array $definition, string $niche): array
    {
        $libraryKey = self::makeLibraryKey($niche, $definition['name']);
        $review = $this->inferReviewStatuses($definition, $niche);
        $category = $definition['category'] ?? 'geral';
        $documentKind = $category === 'consentimento' || $this->looksLikeConsent($definition['name'])
            ? 'consentimento'
            : 'ficha';

        return [
            'library_key' => $libraryKey,
            'name' => $definition['name'],
            'description' => $definition['description'] ?? '',
            'category' => $category,
            'category_label' => FormTemplate::categoryLabels()[$category] ?? $category,
            'specialty' => $niche,
            'specialty_label' => FormTemplate::categoryLabels()[$niche] ?? $niche,
            'document_kind' => $documentKind,
            'field_count' => count($definition['fields'] ?? []),
            'legal_review_status' => $review['legal'],
            'clinical_review_status' => $review['clinical'],
            'content_version' => self::CONTENT_VERSION,
            'reviewed_at' => $review['reviewed_at'],
            'fields' => $definition['fields'],
        ];
    }

    /**
     * @param  array{name: string, description?: string, category?: string}  $definition
     * @return array{legal: string, clinical: string, reviewed_at: string|null}
     */
    private function inferReviewStatuses(array $definition, string $niche): array
    {
        $name = strtolower($definition['name']);
        $fragments = self::APPROVED_NAME_FRAGMENTS[$niche] ?? [];
        foreach ($fragments as $fragment) {
            if (str_contains($name, strtolower($fragment))) {
                $clinical = str_contains($name, 'telemedicina') ? 'pending' : 'approved';

                return [
                    'legal' => 'approved',
                    'clinical' => $clinical,
                    'reviewed_at' => '2026-01-15T00:00:00+00:00',
                ];
            }
        }

        $isConsent = ($definition['category'] ?? '') === 'consentimento' || $this->looksLikeConsent($definition['name']);
        if ($isConsent && in_array($niche, ['estetica', 'odontologia', 'veterinaria'], true)) {
            return [
                'legal' => 'pending',
                'clinical' => 'pending',
                'reviewed_at' => null,
            ];
        }

        return [
            'legal' => 'draft',
            'clinical' => 'draft',
            'reviewed_at' => null,
        ];
    }

    private function looksLikeConsent(string $name): bool
    {
        $lower = strtolower($name);

        return str_contains($lower, 'consentimento')
            || str_contains($lower, 'tcle')
            || str_contains($lower, 'termo de')
            || str_contains($lower, 'autorização')
            || str_contains($lower, 'autorizacao');
    }

    private function normalizeNiche(?string $niche): string
    {
        $niche = strtolower(trim((string) $niche));
        $valid = array_keys(FormTemplate::categoryLabels());

        return in_array($niche, $valid, true) ? $niche : 'estetica';
    }
}
