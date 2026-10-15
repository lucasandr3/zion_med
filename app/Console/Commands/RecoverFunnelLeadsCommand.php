<?php

namespace App\Console\Commands;

use App\Services\FunnelRecoveryService;
use Illuminate\Console\Command;

class RecoverFunnelLeadsCommand extends Command
{
    protected $signature = 'leads:recover-funnel {--dry-run : Lista ações sem enviar e-mail}';

    protected $description = 'Envia e-mails de recuperação (trial acabando, checkout abandonado, onboarding parado)';

    public function handle(FunnelRecoveryService $recovery): int
    {
        $dry = (bool) $this->option('dry-run');
        $stats = $recovery->run($dry);

        $prefix = $dry ? '[dry-run] ' : '';
        $this->info($prefix.'Trial acabando: '.$stats['trial_ending']);
        $this->info($prefix.'Checkout abandonado: '.$stats['checkout_abandoned']);
        $this->info($prefix.'Onboarding parado: '.$stats['onboarding_stalled']);
        $this->info($prefix.'Ignorados: '.$stats['skipped']);

        return self::SUCCESS;
    }
}
