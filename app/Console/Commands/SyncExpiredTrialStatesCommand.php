<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class SyncExpiredTrialStatesCommand extends Command
{
    protected $signature = 'organizations:sync-expired-trials';

    protected $description = 'Atualiza billing_status de organizações com trial expirado sem pagamento confirmado';

    public function handle(): int
    {
        $count = 0;

        Organization::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->whereIn('subscription_status', ['trial', 'inactive'])
            ->orderBy('id')
            ->chunkById(100, function ($organizations) use (&$count) {
                foreach ($organizations as $organization) {
                    $before = $organization->billing_status;
                    $organization->syncExpiredTrialStateIfNeeded();
                    $organization->refresh();
                    if ($organization->billing_status !== $before) {
                        $count++;
                    }
                }
            });

        $this->info("Organizações atualizadas: {$count}.");

        return self::SUCCESS;
    }
}
