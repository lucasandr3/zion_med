<?php

namespace App\Console\Commands;

use App\Services\OrganizationPresenceService;
use Illuminate\Console\Command;

class PruneOrganizationPresenceCommand extends Command
{
    protected $signature = 'organization-presence:prune {--hours=48 : Idade máxima em horas (updated_at) para remover a linha}';

    protected $description = 'Remove registros de presença de organização considerados obsoletos (aba fechada sem beacon, etc.)';

    public function handle(OrganizationPresenceService $service): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $service->pruneStale($hours);
        $this->info("Registros removidos: {$deleted} (limiar: {$hours}h em updated_at).");

        return self::SUCCESS;
    }
}
