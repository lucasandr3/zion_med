<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\InfraFalhaPlataforma;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class PlatformHealthCheckCommand extends Command
{
    protected $signature = 'platform:health-check {--notify : Notifica admins da plataforma se houver falha}';

    protected $description = 'Verifica banco e storage MinIO; opcionalmente alerta admins';

    public function handle(): int
    {
        $failures = [];

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            $this->info('DB: ok');
        } catch (\Throwable $e) {
            $failures[] = 'database';
            $this->error('DB: '.$e->getMessage());
        }

        foreach (['minio_submissions', 'minio_attachments'] as $disk) {
            try {
                $driver = config("filesystems.disks.{$disk}.driver");
                if (! $driver) {
                    $this->warn("Disk {$disk}: não configurado (ignorado)");

                    continue;
                }
                $probe = '.healthcheck/'.now()->format('YmdHis').'.txt';
                Storage::disk($disk)->put($probe, 'ok');
                $ok = Storage::disk($disk)->exists($probe);
                Storage::disk($disk)->delete($probe);
                if (! $ok) {
                    $failures[] = $disk;
                    $this->error("Disk {$disk}: escrita/leitura falhou");
                } else {
                    $this->info("Disk {$disk}: ok");
                }
            } catch (\Throwable $e) {
                $failures[] = $disk;
                $this->error("Disk {$disk}: ".$e->getMessage());
            }
        }

        if ($failures === []) {
            $this->info('Health-check: tudo ok.');

            return self::SUCCESS;
        }

        $this->warn('Falhas: '.implode(', ', $failures));

        if ($this->option('notify')) {
            $admins = User::where('role', Role::PlatformAdmin->value)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new InfraFalhaPlataforma($failures));
                $this->info('Admins notificados (database).');
            }
        }

        return self::FAILURE;
    }
}
