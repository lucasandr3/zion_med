<?php

namespace Database\Seeders;

use App\Models\ReleaseNote;
use Illuminate\Database\Seeder;

class ReleaseNotesSeeder extends Seeder
{
    public function run(): void
    {
        ReleaseNote::query()->updateOrCreate(
            ['version' => '1.1.0'],
            [
                'title' => 'Navegação, notificações e formulários',
                'summary' => 'Melhorias para facilitar o dia a dia: menu reorganizado, notificações mais claras e lista de formulários públicos mais completa.',
                'released_at' => '2026-06-13',
                'is_published' => true,
                'items' => [
                    [
                        'type' => 'feature',
                        'text' => 'Novo menu superior organizado por seções (Início, Operação e Admin) para encontrar as telas com mais facilidade.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Cabeçalho renovado com navegação mais clara e acesso rápido às principais áreas do sistema.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Tela de Notificações redesenhada com filtros simples (Todas / Não lidas) e ações mais visíveis para marcar ou limpar.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Aviso automático quando uma nova versão do sistema está disponível, com botão para atualizar na hora.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Lista de Formulários públicos agora mostra quantas respostas cada formulário recebeu e permite navegar por páginas quando há muitos itens.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Melhorias gerais de layout e organização em várias telas do sistema.',
                    ],
                ],
            ],
        );

        ReleaseNote::query()->updateOrCreate(
            ['version' => '1.2.0'],
            [
                'title' => 'Novidades, Google no Link na bio e formulário público',
                'summary' => 'Acompanhe as atualizações do Gestgo, exiba avaliações do Google na sua página pública e aproveite melhorias no preenchimento de formulários.',
                'released_at' => '2026-06-14',
                'is_published' => true,
                'items' => [
                    [
                        'type' => 'feature',
                        'text' => 'Nova área "Novidades e versão" para acompanhar tudo que mudou em cada atualização do Gestgo.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Indicador no menu avisa quando há novidades que você ainda não viu.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Avaliações do Google exibidas na página pública do Link na bio: nota, comentários recentes e botão para avaliar.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Formulário público com experiência melhorada para quem preenche pelo celular ou computador.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Página de login com visual mais limpo e ajustes de layout no Link na bio e nas integrações.',
                    ],
                ],
            ],
        );
    }
}
