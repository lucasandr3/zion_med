<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Person;
use Illuminate\Console\Command;

class PurgeInactivePeopleCommand extends Command
{
    protected $signature = 'people:purge-inactive';

    protected $description = 'Remove fichas (people) sem protocolos mais antigas que data_retention_years da organização';

    public function handle(): int
    {
        $total = 0;
        Organization::query()->whereNotNull('data_retention_years')->chunkById(50, function ($orgs) use (&$total): void {
            foreach ($orgs as $org) {
                $years = (int) $org->data_retention_years;
                if ($years < 1) {
                    continue;
                }
                $cutoff = now()->subYears($years);
                $n = Person::withoutGlobalScopes()
                    ->where('organization_id', $org->id)
                    ->whereDoesntHave('submissions')
                    ->where('created_at', '<', $cutoff)
                    ->delete();
                $total += $n;
            }
        });

        $this->info("Registros removidos: {$total}.");

        return self::SUCCESS;
    }
}
