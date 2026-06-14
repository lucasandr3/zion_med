<?php

namespace Database\Seeders;

use App\Models\ReleaseNote;
use Illuminate\Database\Seeder;

class ReleaseNotesSeeder extends Seeder
{
    public function run(): void
    {
        ReleaseNote::query()->updateOrCreate(
            ['version' => '1.0.0'],
            [
                'title' => 'Novidades e versão',
                'summary' => 'Acompanhe aqui as melhorias e novidades do Gestgo a cada atualização.',
                'released_at' => '2026-06-14',
                'is_published' => true,
                'items' => [
                    [
                        'type' => 'feature',
                        'text' => 'Nova área de Novidades e versão para acompanhar o que mudou em cada release.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Badge no cabeçalho avisa quando há atualizações que você ainda não viu.',
                    ],
                ],
            ],
        );
    }
}
