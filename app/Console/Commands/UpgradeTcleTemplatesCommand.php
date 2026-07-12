<?php

namespace App\Console\Commands;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Services\TemplateLibraryCatalog;
use Illuminate\Console\Command;

class UpgradeTcleTemplatesCommand extends Command
{
    protected $signature = 'templates:upgrade-tcle
                            {--organization= : ID da organização (omite para todas)}
                            {--mode=merge : merge|replace|scaffold}
                            {--install-missing : Instala TCLEs da biblioteca ainda ausentes na organização}
                            {--force : Permite --mode=replace mesmo com protocolos já enviados}
                            {--dry-run : Apenas simula}';

    protected $description = 'Atualiza TCLEs já instalados para a estrutura CFM (v2): merge de campos, scaffold ou replace';

    public function handle(TemplateLibraryCatalog $catalog): int
    {
        $mode = (string) $this->option('mode');
        if (! in_array($mode, ['merge', 'replace', 'scaffold'], true)) {
            $this->error('Mode inválido. Use merge, replace ou scaffold.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $installMissing = (bool) $this->option('install-missing');
        $orgOpt = $this->option('organization');

        $orgQuery = Organization::query()->orderBy('id');
        if ($orgOpt !== null && $orgOpt !== '') {
            $orgQuery->where('id', (int) $orgOpt);
        }

        $stats = [
            'orgs' => 0,
            'upgraded' => 0,
            'up_to_date' => 0,
            'skipped' => 0,
            'installed' => 0,
            'would' => 0,
        ];

        foreach ($orgQuery->cursor() as $organization) {
            $stats['orgs']++;

            if ($installMissing) {
                $stats['installed'] += $this->installMissingForOrganization($catalog, $organization, $dryRun);
            }

            $templates = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where(function ($q) {
                    $q->where('document_kind', 'consentimento')
                        ->orWhere('category', 'consentimento')
                        ->orWhere('name', 'like', '%TCLE%')
                        ->orWhere('name', 'like', '%Consentimento%');
                })
                ->orderBy('id')
                ->get();

            foreach ($templates as $template) {
                // Não tocar autorização de imagem / ciência LGPD isolada no modo merge/replace sem match clínico
                $nameLower = mb_strtolower((string) $template->name);
                if ((str_contains($nameLower, 'imagem') || str_contains($nameLower, 'ciência lgpd') || str_contains($nameLower, 'ciencia lgpd'))
                    && ! str_contains($nameLower, 'tcle')) {
                    $stats['skipped']++;
                    $this->line(sprintf(
                        'Org %d · #%d %s → skipped (documento não-TCLE clínico)',
                        $organization->id,
                        $template->id,
                        $template->name
                    ));
                    continue;
                }

                if ($mode === 'replace' && ! $force) {
                    $hasSubs = FormSubmission::withoutGlobalScopes()
                        ->where('template_id', $template->id)
                        ->exists();
                    if ($hasSubs) {
                        $stats['skipped']++;
                        $this->warn(sprintf(
                            'Org %d · #%d %s → skipped replace (há protocolos; use --force)',
                            $organization->id,
                            $template->id,
                            $template->name
                        ));
                        continue;
                    }
                }

                $result = $catalog->upgradeConsentTemplate($template->fresh(['fields']), $mode, $dryRun);
                $status = $result['status'];
                if ($status === 'upgraded') {
                    $stats['upgraded']++;
                } elseif ($status === 'up_to_date') {
                    $stats['up_to_date']++;
                } elseif (str_starts_with($status, 'would_')) {
                    $stats['would']++;
                } else {
                    $stats['skipped']++;
                }

                $this->line(sprintf(
                    'Org %d · #%d %s → %s (+%d ~%d -%d) %s',
                    $organization->id,
                    $template->id,
                    $template->name,
                    $status,
                    $result['fields_created'],
                    $result['fields_updated'],
                    $result['fields_removed'],
                    $result['message']
                ));
            }
        }

        $label = $dryRun ? 'SIMULAÇÃO' : 'EXECUÇÃO';
        $this->newLine();
        $this->info(sprintf(
            '%s concluída · orgs=%d · upgraded=%d · up_to_date=%d · would=%d · skipped=%d · installed_missing=%d',
            $label,
            $stats['orgs'],
            $stats['upgraded'],
            $stats['up_to_date'],
            $stats['would'],
            $stats['skipped'],
            $stats['installed']
        ));
        if ($dryRun) {
            $this->comment('Nenhuma alteração salva. Rode sem --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }

    private function installMissingForOrganization(
        TemplateLibraryCatalog $catalog,
        Organization $organization,
        bool $dryRun
    ): int {
        $installed = 0;
        $preferred = [
            'estetica__tcle_termo_de_consentimento_livre_e_esclarecido',
            'geral__termo_de_consentimento_atendimentoprocedimento',
            'odontologia__tcle_tratamentos_odontologicos',
        ];

        $niche = strtolower((string) ($organization->niche ?? 'estetica'));
        $keys = match (true) {
            str_contains($niche, 'odonto') => ['odontologia__tcle_tratamentos_odontologicos'],
            $niche === 'estetica' => [
                'estetica__tcle_termo_de_consentimento_livre_e_esclarecido',
            ],
            default => $preferred,
        };

        foreach ($keys as $libraryKey) {
            $def = $catalog->findByKey($libraryKey);
            if ($def === null) {
                continue;
            }
            $exists = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where(function ($q) use ($libraryKey, $def) {
                    $q->where('library_key', $libraryKey)
                        ->orWhere('name', $def['name']);
                })
                ->exists();
            if ($exists) {
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('Org %d · would install %s', $organization->id, $libraryKey));
                $installed++;
                continue;
            }

            $catalog->install($organization, $libraryKey, null);
            $this->info(sprintf('Org %d · installed %s', $organization->id, $libraryKey));
            $installed++;
        }

        return $installed;
    }
}
