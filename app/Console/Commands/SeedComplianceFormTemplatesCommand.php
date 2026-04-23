<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Console\Command;

class SeedComplianceFormTemplatesCommand extends Command
{
    protected $signature = 'templates:seed-compliance-extras
                            {--organization= : ID numérico da organização (omite para todas)}';

    protected $description = 'Cria os templates extras (Telemedicina, LGPD, Checklist OMS) onde ainda não existem (idempotente por nome)';

    public function handle(): int
    {
        $orgId = $this->option('organization');
        $query = Organization::query()->orderBy('id');

        if ($orgId !== null && $orgId !== '') {
            $id = (int) $orgId;
            $query->where('id', $id);
            if (! Organization::query()->where('id', $id)->exists()) {
                $this->error("Organização #{$id} não encontrada.");

                return self::FAILURE;
            }
        }

        $totalCreated = 0;
        $orgsProcessed = 0;

        foreach ($query->cursor() as $organization) {
            $owner = User::withoutGlobalScopes()->where('organization_id', $organization->id)->first();
            $n = FormTemplateSeeder::ensureComplianceExtrasForOrganization($organization, $owner);
            $totalCreated += $n;
            $orgsProcessed++;
            if ($n > 0) {
                $this->line("Organização #{$organization->id} ({$organization->name}): +{$n} template(s).");
            }
        }

        if ($orgsProcessed === 0) {
            $this->warn('Nenhuma organização encontrada.');

            return self::SUCCESS;
        }

        $this->info("Processadas {$orgsProcessed} organização(ões). Templates criados no total: {$totalCreated}.");

        return self::SUCCESS;
    }
}
