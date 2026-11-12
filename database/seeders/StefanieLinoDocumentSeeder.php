<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Atalho local. Em produção use o comando Artisan apontando a conta real:
 *
 *   php artisan migrate --force
 *   php artisan templates:seed-stefanie-lino --list
 *   php artisan templates:seed-stefanie-lino --organization=ID --force
 *
 * Ou o wrapper:
 *   php artisan gestgo:provision-stefanie-lino --organization=ID --migrate --force
 */
class StefanieLinoDocumentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error(
                'Em produção não use db:seed. Rode: php artisan templates:seed-stefanie-lino --organization=ID --force'
            );

            return;
        }

        $exit = Artisan::call('templates:seed-stefanie-lino', [
            '--create-local-demo' => true,
            '--force' => true,
        ]);

        $this->command?->getOutput()?->write(Artisan::output());

        if ($exit !== 0) {
            throw new \RuntimeException('Falha ao provisionar documentos Stefanie Lino (exit '.$exit.').');
        }
    }
}
