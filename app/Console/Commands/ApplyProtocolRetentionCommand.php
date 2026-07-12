<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ProtocolRetentionService;
use Illuminate\Console\Command;

class ApplyProtocolRetentionCommand extends Command
{
    protected $signature = 'protocols:apply-retention {--dry-run : Apenas conta elegíveis, sem alterar dados}';

    protected $description = 'Aplica política de retenção em protocolos antigos (anonimização ou exclusão)';

    public function handle(ProtocolRetentionService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        Organization::query()
            ->whereNotNull('protocol_retention_years')
            ->where('protocol_retention_years', '>=', 1)
            ->chunkById(50, function ($orgs) use ($service, $dryRun, &$total): void {
                foreach ($orgs as $org) {
                    $n = $service->applyForOrganization($org, $dryRun);
                    if ($n > 0) {
                        $this->line(sprintf(
                            'Org #%d (%s): %d protocolo(s) %s',
                            $org->id,
                            $org->name,
                            $n,
                            $dryRun ? 'elegíveis' : 'processados'
                        ));
                    }
                    $total += $n;
                }
            });

        $this->info(($dryRun ? 'Elegíveis' : 'Processados').": {$total}.");

        return self::SUCCESS;
    }
}
