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

        ReleaseNote::query()->updateOrCreate(
            ['version' => '1.3.0'],
            [
                'title' => 'Consentimentos (TCLE), compliance e formulário público reforçado',
                'summary' => 'Pacote focado em Termo de Consentimento Livre e Esclarecido: modelos alinhados às boas práticas do CFM, publicação mais segura, evidências no preenchimento e painel de compliance para a clínica.',
                'released_at' => '2026-07-12',
                'is_published' => true,
                'items' => [
                    [
                        'type' => 'feature',
                        'text' => 'Novos modelos de TCLE na biblioteca (clínica/estética e odontologia), com seções de procedimento, riscos, benefícios, alternativas, cuidados, direito de recusa, LGPD clínica e assinaturas — alinhados à Recomendação CFM nº 1/2016 (orientação ética).',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Checklist de consentimento informado na tela de Campos do modelo, para conferir o que ainda falta antes de publicar.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Botão "Aplicar estrutura TCLE": organiza o formulário em etapas clínicas e completa notices básicos quando o termo estiver incompleto.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Publicação de link de consentimento bloqueada se faltar conteúdo essencial visível ao paciente (procedimento, riscos/benefícios, alternativas, autorização e assinatura).',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Formulário público com etapas clínicas, leitura do termo até o fim (scroll), declaração de compreensão, ciência de privacidade e quiz de compreensão (quando configurado).',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Modo assistido com coassinatura do profissional no mesmo documento do paciente.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Ciclo de vida do consentimento nos protocolos: validade, revogação com motivo, reconsentimento e resumo na ficha da pessoa.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Painel de compliance de consentimentos no dashboard (vencidos, revogados, sem snapshot, taxa de revogação).',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Retenção de protocolos configurável na clínica (anonimizar ou excluir após o prazo), com job automático de aplicação.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Verificação pública do documento com mais evidências (compreensão, scroll, responsável/testemunha, revogação e retenção).',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Campos condicionais nos modelos e bloco de responsável legal/testemunha com regras de exibição.',
                    ],
                    [
                        'type' => 'feature',
                        'text' => 'Comparação de versões do modelo ao publicar, para revisar o que mudou desde a última versão.',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Autorização de imagem/marketing permanece em documento separado do TCLE clínico, para manter finalidades distintas (boa prática LGPD).',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Bloqueio de agendamento Feegow quando o consentimento do paciente estiver inválido ou ausente (quando a integração estiver ativa).',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Comando para atualizar TCLEs já instalados nas clínicas: php artisan templates:upgrade-tcle (use --dry-run antes de aplicar).',
                    ],
                    [
                        'type' => 'improvement',
                        'text' => 'Chat de ajuda e ajustes de estabilidade (CORS, landing e ambiente) incluídos no pacote recente de atualizações.',
                    ],
                    [
                        'type' => 'fix',
                        'text' => 'Correção na criação/instalação de modelos da biblioteca (classe Rule de validação).',
                    ],
                ],
            ],
        );
    }
}
