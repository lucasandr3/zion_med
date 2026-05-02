<?php

namespace App\Console\Commands;

use App\Models\FormField;
use App\Models\FormTemplate;
use Database\Seeders\Definitions\EsteticaFormTemplatePack;
use Database\Seeders\Definitions\EsteticaStaffFieldsPack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEsteticaPublicPatientFieldsCommand extends Command
{
    protected $signature = 'templates:sync-estetica-public-patient
                            {--organization= : ID da organização (omite para processar todas)}
                            {--dry-run : Apenas simula alterações}';

    protected $description = 'Ajusta templates Estética existentes: mantém no público só campos do paciente e move campos da equipe para protocolo';

    public function handle(): int
    {
        $templatesByName = collect(EsteticaFormTemplatePack::templates())
            ->keyBy(fn (array $t) => (string) $t['name']);
        $names = $templatesByName->keys()->values()->all();

        $query = FormTemplate::withoutGlobalScopes()
            ->whereIn('name', $names)
            ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('organization_id')
            ->orderBy('id');

        $orgOpt = $this->option('organization');
        if ($orgOpt !== null && $orgOpt !== '') {
            $query->where('organization_id', (int) $orgOpt);
        }

        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $created = 0;
        $updated = 0;
        $removed = 0;

        foreach ($query->cursor() as $template) {
            $processed++;

            $tplDef = $templatesByName->get($template->name);
            if (! is_array($tplDef)) {
                continue;
            }

            $patientFields = collect($tplDef['fields'] ?? [])
                ->filter(fn ($f) => is_array($f) && isset($f['name_key']))
                ->keyBy(fn ($f) => (string) $f['name_key']);

            $staffKeys = collect(EsteticaStaffFieldsPack::fieldsForTemplateName((string) $template->name))
                ->map(fn (array $f) => (string) $f['name_key'])
                ->unique()
                ->values()
                ->all();
            $staffSet = array_fill_keys($staffKeys, true);

            $existingByKey = $template->fields->keyBy('name_key');
            $templateCreated = 0;
            $templateUpdated = 0;
            $templateRemoved = 0;

            $runner = function () use (
                $template,
                $patientFields,
                $staffSet,
                $existingByKey,
                &$templateCreated,
                &$templateUpdated,
                &$templateRemoved
            ): void {
                foreach ($existingByKey as $key => $field) {
                    $k = (string) $key;
                    if (! isset($staffSet[$k])) {
                        continue;
                    }
                    $field->delete();
                    $templateRemoved++;
                }

                foreach ($patientFields as $key => $def) {
                    $k = (string) $key;
                    $required = (bool) ($def['required'] ?? false);
                    $sortOrder = (int) ($def['sort_order'] ?? 0);
                    $options = isset($def['options']) && is_array($def['options']) ? array_values($def['options']) : null;
                    $optionsJson = $options !== null ? ['options' => $options] : null;

                    /** @var FormField|null $current */
                    $current = $existingByKey->get($k);
                    if (! $current) {
                        FormField::create([
                            'template_id' => $template->id,
                            'name_key' => $k,
                            'label' => (string) ($def['label'] ?? $k),
                            'type' => (string) ($def['type'] ?? 'text'),
                            'required' => $required,
                            'options_json' => $optionsJson,
                            'sort_order' => $sortOrder,
                        ]);
                        $templateCreated++;
                        continue;
                    }

                    $currentOptions = is_array($current->options_json) ? $current->options_json : null;
                    $needsUpdate =
                        (string) $current->label !== (string) ($def['label'] ?? $k) ||
                        (string) $current->type !== (string) ($def['type'] ?? 'text') ||
                        (bool) $current->required !== $required ||
                        (int) $current->sort_order !== $sortOrder ||
                        $currentOptions !== $optionsJson;

                    if (! $needsUpdate) {
                        continue;
                    }

                    $current->update([
                        'label' => (string) ($def['label'] ?? $k),
                        'type' => (string) ($def['type'] ?? 'text'),
                        'required' => $required,
                        'options_json' => $optionsJson,
                        'sort_order' => $sortOrder,
                    ]);
                    $templateUpdated++;
                }
            };

            if ($dryRun) {
                foreach ($existingByKey as $key => $field) {
                    $k = (string) $key;
                    if (! isset($staffSet[$k])) {
                        continue;
                    }
                    $templateRemoved++;
                }
                foreach ($patientFields as $key => $def) {
                    $k = (string) $key;
                    /** @var FormField|null $current */
                    $current = $existingByKey->get($k);
                    if (! $current) {
                        $templateCreated++;
                        continue;
                    }
                    $required = (bool) ($def['required'] ?? false);
                    $sortOrder = (int) ($def['sort_order'] ?? 0);
                    $options = isset($def['options']) && is_array($def['options']) ? array_values($def['options']) : null;
                    $optionsJson = $options !== null ? ['options' => $options] : null;
                    $currentOptions = is_array($current->options_json) ? $current->options_json : null;
                    $needsUpdate =
                        (string) $current->label !== (string) ($def['label'] ?? $k) ||
                        (string) $current->type !== (string) ($def['type'] ?? 'text') ||
                        (bool) $current->required !== $required ||
                        (int) $current->sort_order !== $sortOrder ||
                        $currentOptions !== $optionsJson;
                    if ($needsUpdate) {
                        $templateUpdated++;
                    }
                }
            } else {
                DB::transaction($runner);
            }

            $created += $templateCreated;
            $updated += $templateUpdated;
            $removed += $templateRemoved;

            if (($templateCreated + $templateUpdated + $templateRemoved) > 0) {
                $this->line(sprintf(
                    'Template #%d (%s) org=%d -> +%d ~%d -%d',
                    $template->id,
                    $template->name,
                    (int) $template->organization_id,
                    $templateCreated,
                    $templateUpdated,
                    $templateRemoved
                ));
            }
        }

        if ($processed === 0) {
            $this->warn('Nenhum template de Estética encontrado para processar.');

            return self::SUCCESS;
        }

        $mode = $dryRun ? 'SIMULAÇÃO' : 'EXECUÇÃO';
        $this->info("{$mode} concluída. Templates processados: {$processed} | criados: {$created} | atualizados: {$updated} | removidos: {$removed}.");
        if ($dryRun) {
            $this->comment('Nenhuma alteração foi salva. Execute novamente sem --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
