<?php

namespace App\Jobs;

use App\Models\FormImport;
use App\Services\FormImport\FormImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFormImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public int $formImportId) {}

    public function handle(FormImportService $service): void
    {
        $import = FormImport::query()->find($this->formImportId);
        if (! $import || in_array($import->status, ['completed', 'committed'], true)) {
            return;
        }
        $service->process($import);
    }
}
