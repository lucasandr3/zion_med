<?php

namespace App\Console\Commands;

use App\Models\FormImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredFormImportsCommand extends Command
{
    protected $signature = 'form-imports:purge-expired {--dry-run : Apenas lista elegíveis}';

    protected $description = 'Remove fontes e registros de importações de ficha após o prazo de retenção';

    public function handle(): int
    {
        $days = max(1, (int) config('form_import.retention_days', 30));
        $cutoff = now()->subDays($days);
        $dry = (bool) $this->option('dry-run');
        $total = 0;

        FormImport::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($dry, &$total): void {
                foreach ($rows as $import) {
                    $total++;
                    if ($dry) {
                        $this->line(sprintf('#%d %s %s', $import->id, $import->status, $import->created_at));
                        continue;
                    }
                    if ($import->source_path) {
                        try {
                            Storage::disk($import->source_disk ?: 'local')->delete($import->source_path);
                        } catch (\Throwable) {
                        }
                    }
                    $import->pages()->delete();
                    if ($import->status === 'committed' && $import->template_id) {
                        $import->update([
                            'source_path' => null,
                            'extracted_schema_json' => null,
                            'warnings_json' => null,
                        ]);
                    } else {
                        $import->delete();
                    }
                }
            });

        $this->info(($dry ? 'Elegíveis' : 'Processados').": {$total} (retenção {$days} dias).");

        return self::SUCCESS;
    }
}
