<?php

declare(strict_types=1);

namespace Database\Seeders\Definitions;

/**
 * Campos-base de TCLE alinhados à Recomendação CFM nº 1/2016 e a modelos
 * usuais no mercado brasileiro (médico e odontológico).
 *
 * A Recomendação CFM nº 1/2016 não é lei federal; é orientação ética do CFM.
 * Os textos abaixo são modelos editáveis — a clínica deve personalizar riscos
 * e particularidades do procedimento/paciente.
 *
 * @phpstan-type FieldDef array{type: string, label: string, name_key: string, sort_order: int, required?: bool, options?: array<int, string>, clinical_step_kind?: string}
 */
final class TcleConsentFieldsBuilder
{
    /**
     * TCLE clínico geral / estética / procedimentos ambulatoriais.
     *
     * @return list<FieldDef>
     */
    public static function medicalProcedureFields(): array
    {
        $o = 0;
        $f = static function (
            string $type,
            string $label,
            string $key,
            string $step,
            bool $req = false,
            ?array $opt = null
        ) use (&$o): array {
            $row = [
                'type' => $type,
                'label' => $label,
                'name_key' => $key,
                'sort_order' => ++$o,
                'required' => $req,
                'clinical_step_kind' => $step,
            ];
            if ($opt !== null) {
                $row['options'] = $opt;
            }

            return $row;
        };

        return [
            $f('heading', 'Termo de Consentimento Livre e Esclarecido (TCLE)', 'hdr_tcle', 'dados_paciente'),
            $f(
                'notice',
                'Este documento tem por objetivo informar, de forma clara e acessível, o procedimento indicado, seus objetivos, benefícios esperados, riscos, desconfortos, alternativas, cuidados e o direito de recusar o tratamento, para que você decida de forma livre e esclarecida. Linguagem alinhada à Recomendação CFM nº 1/2016 (orientação ética — personalize com o caso clínico).',
                'notice_objetivo',
                'dados_paciente'
            ),

            $f('heading', '1. Identificação do paciente', 'hdr_paciente', 'dados_paciente'),
            $f('text', 'Nome completo do paciente', 'nome_paciente', 'dados_paciente', true),
            $f('date', 'Data de nascimento', 'data_nascimento', 'dados_paciente', false),
            $f('text', 'Documento de identidade (RG/CPF)', 'documento_paciente', 'dados_paciente', false),
            $f('text', 'Endereço', 'endereco_paciente', 'dados_paciente', false),
            $f('text', 'Contato de emergência (nome, parentesco e telefone)', 'contato_emergencia', 'dados_paciente', false),
            $f('textarea', 'Queixa e/ou condições de saúde relevantes informadas pelo paciente', 'queixa_condicoes', 'dados_paciente', false),

            $f('heading', '2. Profissional responsável', 'hdr_profissional', 'dados_paciente'),
            $f('text', 'Nome do profissional responsável', 'nome_profissional', 'dados_paciente', true),
            $f('text', 'Registro profissional (CRM/CRO/outro) e RQE, se houver', 'registro_profissional', 'dados_paciente', true),
            $f('text', 'Especialidade', 'especialidade_profissional', 'dados_paciente', false),
            $f('text', 'Contato de emergência do profissional/clínica', 'contato_profissional', 'dados_paciente', false),

            $f('heading', '3. Justificativa, objetivos e descrição do procedimento', 'hdr_procedimento', 'descricao_procedimento'),
            $f(
                'notice',
                'O profissional deve descrever, em linguagem acessível, o diagnóstico ou hipótese diagnóstica, a justificativa da conduta, o que será feito e, se houver, a anestesia. Espaços em branco devem ser preenchidos para o seu caso (Recomendação CFM nº 1/2016).',
                'notice_procedimento',
                'descricao_procedimento'
            ),
            $f('textarea', 'Diagnóstico / hipótese diagnóstica e justificativa do tratamento proposto', 'diagnostico_justificativa', 'descricao_procedimento', true),
            $f('textarea', 'Descrição do procedimento / cirurgia / tratamento (o que será realizado)', 'descricao_procedimento', 'descricao_procedimento', true),
            $f('textarea', 'Anestesia (se houver): tipo e forma de realização', 'anestesia', 'descricao_procedimento', false),
            $f('textarea', 'Duração estimada e possíveis desconfortos durante o procedimento', 'duracao_desconfortos', 'descricao_procedimento', false),

            $f('heading', '4. Riscos, intercorrências e benefícios', 'hdr_riscos', 'riscos_beneficios'),
            $f(
                'notice',
                'Todo procedimento de saúde envolve riscos. A lista abaixo deve ser personalizada para o procedimento e para o seu perfil clínico. Esta lista não esgota todas as possibilidades. Não há garantia absoluta de resultado: o profissional compromete-se a agir com zelo e técnica adequados (obrigação de meio).',
                'notice_riscos',
                'riscos_beneficios'
            ),
            $f('textarea', 'Riscos, efeitos colaterais e intercorrências possíveis (incluindo as mais comuns e as relevantes, ainda que menos frequentes)', 'riscos_intercorrencias', 'riscos_beneficios', true),
            $f('textarea', 'Riscos relacionados à anestesia (se aplicável)', 'riscos_anestesia', 'riscos_beneficios', false),
            $f('textarea', 'Benefícios esperados e prognóstico (sem garantia de resultado)', 'beneficios_prognostico', 'riscos_beneficios', true),

            $f('heading', '5. Alternativas e consequências da não realização', 'hdr_alternativas', 'alternativas'),
            $f(
                'notice',
                'Você deve ser informado(a) sobre outras opções de tratamento (incluindo condutas menos invasivas, quando existirem) e sobre o que pode ocorrer se o procedimento não for realizado.',
                'notice_alternativas',
                'alternativas'
            ),
            $f('textarea', 'Alternativas terapêuticas (vantagens e desvantagens) e motivo da escolha proposta', 'alternativas_tratamento', 'alternativas', true),
            $f('textarea', 'Consequências possíveis da não realização do procedimento', 'consequencias_nao_realizar', 'alternativas', true),

            $f('heading', '6. Cuidados pré e pós-procedimento', 'hdr_cuidados', 'declaracoes'),
            $f('textarea', 'Cuidados pré-procedimento (responsabilidade do paciente)', 'cuidados_pre', 'declaracoes', false),
            $f('textarea', 'Cuidados pós-procedimento e recuperação (responsabilidade do paciente)', 'cuidados_pos', 'declaracoes', true),

            $f('heading', '7. Direito de recusa e esclarecimentos', 'hdr_recusa', 'declaracoes'),
            $f(
                'notice',
                'Você tem o direito de recusar o procedimento, total ou parcialmente, sem prejuízo ao cuidado adequado nas demais opções disponíveis. Pode levar este termo para análise e esclarecer dúvidas com o profissional antes de assinar. Espaço para anotações de dúvidas abaixo.',
                'notice_recusa',
                'declaracoes'
            ),
            $f('textarea', 'Dúvidas do paciente / anotações para esclarecimento com o profissional', 'duvidas_paciente', 'declaracoes', false),

            $f('heading', '8. Proteção de dados (LGPD) — finalidade clínica', 'hdr_lgpd', 'privacidade_lgpd'),
            $f(
                'notice',
                'Os dados pessoais e de saúde serão tratados para atendimento clínico, registro do consentimento e cumprimento de obrigações legais da clínica (LGPD — Lei 13.709/2018). Uso de imagem para marketing ou compartilhamento com parceiros comerciais, se houver, deve constar em documento separado e específico. A clínica é controladora dos dados do paciente.',
                'notice_lgpd_clinico',
                'privacidade_lgpd'
            ),
            $f('checkbox', 'Autorizo o tratamento dos meus dados pessoais e de saúde para finalidades clínicas e legais informadas neste termo', 'lgpd_finalidade_clinica', 'privacidade_lgpd', true),

            $f('heading', '9. Declarações do paciente', 'hdr_declaracoes', 'declaracoes'),
            $f('checkbox', 'Declaro que li e compreendi as informações sobre o procedimento, riscos, benefícios, alternativas e cuidados', 'decl_leitura', 'declaracoes', true),
            $f('checkbox', 'Declaro que minhas dúvidas foram esclarecidas pelo profissional responsável', 'decl_duvidas', 'declaracoes', true),
            $f('checkbox', 'Declaro ter informado ao profissional as condições de saúde e comorbidades de que tenho conhecimento', 'decl_verdade', 'declaracoes', true),
            $f('checkbox', 'Estou ciente de que posso recusar ou revogar o consentimento a qualquer momento antes do procedimento', 'decl_recusa_revogacao', 'declaracoes', true),
            $f('checkbox', 'Autorizo a realização do procedimento de forma livre e esclarecida', 'decl_autorizo_proc', 'declaracoes', true),
            $f('checkbox', 'Comprometo-me a seguir os cuidados pré e pós-procedimento orientados', 'decl_pos', 'declaracoes', true),
            $f('checkbox', 'Recebi (ou terei acesso a) cópia deste termo e dos contatos de emergência informados', 'decl_copia', 'declaracoes', false),

            $f('heading', '10. Assinaturas', 'hdr_assinaturas', 'assinaturas'),
            $f('signature', 'Assinatura do paciente', 'assinatura_paciente', 'assinaturas', true),
            $f('signature', 'Assinatura do responsável legal (se menor ou incapaz)', 'assinatura_responsavel', 'assinaturas', false),
            $f('text', 'Nome e parentesco do responsável legal (se aplicável)', 'nome_responsavel', 'assinaturas', false),
            $f('signature', 'Assinatura do profissional responsável', 'assinatura_profissional', 'assinaturas', true),
            $f('text', 'Local', 'local_assinatura', 'assinaturas', false),
        ];
    }

    /**
     * TCLE odontológico alinhado a modelos de CRO e prática odontológica brasileira.
     *
     * @return list<FieldDef>
     */
    public static function odontologicFields(): array
    {
        $o = 0;
        $f = static function (
            string $type,
            string $label,
            string $key,
            string $step,
            bool $req = false,
            ?array $opt = null
        ) use (&$o): array {
            $row = [
                'type' => $type,
                'label' => $label,
                'name_key' => $key,
                'sort_order' => ++$o,
                'required' => $req,
                'clinical_step_kind' => $step,
            ];
            if ($opt !== null) {
                $row['options'] = $opt;
            }

            return $row;
        };

        return [
            $f('heading', 'TCLE para tratamentos odontológicos', 'hdr_tcle_odonto', 'dados_paciente'),
            $f(
                'notice',
                'Este termo registra que você foi informado(a) pelo(a) cirurgião(ã)-dentista sobre o tratamento proposto, riscos, alternativas, cuidados e custos relevantes, podendo decidir de forma livre e esclarecida (Código de Ética Odontológica / práticas de CRO). Personalize os campos ao plano de tratamento do paciente.',
                'notice_objetivo_odonto',
                'dados_paciente'
            ),

            $f('text', 'Nome completo do beneficiário / paciente', 'nome_paciente', 'dados_paciente', true),
            $f('text', 'RG / CPF do paciente', 'documento_paciente', 'dados_paciente', false),
            $f('text', 'Nome do(a) cirurgião(ã)-dentista', 'nome_dentista', 'dados_paciente', true),
            $f('text', 'CRO', 'cro_dentista', 'dados_paciente', true),
            $f('text', 'Número da Guia / Plano de Tratamento Odontológico (se houver)', 'guia_tratamento', 'dados_paciente', false),

            $f('heading', 'Procedimento proposto', 'hdr_proc_odonto', 'descricao_procedimento'),
            $f('textarea', 'Descrição do tratamento / procedimentos propostos', 'descricao_procedimento', 'descricao_procedimento', true),
            $f('textarea', 'Propósitos do tratamento e benefícios esperados', 'beneficios_prognostico', 'riscos_beneficios', true),

            $f('heading', 'Riscos e limitações', 'hdr_riscos_odonto', 'riscos_beneficios'),
            $f(
                'notice',
                'O tratamento odontológico está sujeito a riscos e intercorrências. O resultado esperado pode não se concretizar por fatores individuais (resposta biológica), limitações da ciência e variações locais ou sistêmicas. O(a) cirurgião(ã)-dentista compromete-se a utilizar técnicas e materiais adequados e responde por falha técnica na execução.',
                'notice_riscos_odonto',
                'riscos_beneficios'
            ),
            $f('textarea', 'Riscos e intercorrências específicos do tratamento proposto', 'riscos_intercorrencias', 'riscos_beneficios', true),
            $f('textarea', 'Fatores individuais / limitações que podem afetar o resultado', 'limitacoes_resultado', 'riscos_beneficios', false),

            $f('heading', 'Alternativas e custos', 'hdr_alt_odonto', 'alternativas'),
            $f('textarea', 'Alternativas de tratamento (vantagens e desvantagens) e opção escolhida', 'alternativas_tratamento', 'alternativas', true),
            $f('textarea', 'Informações sobre propósitos, custos e condições comerciais relevantes do plano (CDC)', 'custos_propositos', 'alternativas', false),
            $f('textarea', 'Consequências da não realização do tratamento', 'consequencias_nao_realizar', 'alternativas', true),

            $f('heading', 'Cuidados e compromissos', 'hdr_cuidados_odonto', 'declaracoes'),
            $f('textarea', 'Cuidados pré e pós-operatórios / orientações para êxito do tratamento', 'cuidados_pos', 'declaracoes', true),
            $f('textarea', 'Histórico de saúde geral discutido com o(a) dentista (doenças conhecidas)', 'historico_saude', 'declaracoes', false),
            $f('textarea', 'Dúvidas do paciente', 'duvidas_paciente', 'declaracoes', false),

            $f('heading', 'Proteção de dados (LGPD)', 'hdr_lgpd_odonto', 'privacidade_lgpd'),
            $f(
                'notice',
                'Dados pessoais e de saúde serão tratados para atendimento odontológico, registro do consentimento e obrigações legais. Autorizações de imagem/marketing devem ser objeto de termo específico, separado deste TCLE.',
                'notice_lgpd_odonto',
                'privacidade_lgpd'
            ),
            $f('checkbox', 'Autorizo o tratamento dos meus dados para finalidades clínicas e legais deste atendimento', 'lgpd_finalidade_clinica', 'privacidade_lgpd', true),

            $f('heading', 'Declarações', 'hdr_decl_odonto', 'declaracoes'),
            $f('checkbox', 'Fui informado(a) sobre riscos, intercorrências e limitações do resultado', 'decl_riscos', 'declaracoes', true),
            $f('checkbox', 'Fui esclarecido(a) sobre alternativas de tratamento e optei pela proposta descrita', 'decl_alternativas', 'declaracoes', true),
            $f('checkbox', 'Fui orientado(a) sobre cuidados pré/pós e comprometo-me a segui-los, comunicando alterações e comparecendo às consultas', 'decl_cuidados', 'declaracoes', true),
            $f('checkbox', 'Aceito e autorizo a execução do tratamento de forma livre e esclarecida', 'decl_autorizo_proc', 'declaracoes', true),
            $f('checkbox', 'Estou ciente de que posso recusar ou revogar o consentimento antes do procedimento', 'decl_recusa_revogacao', 'declaracoes', true),

            $f('heading', 'Assinaturas', 'hdr_ass_odonto', 'assinaturas'),
            $f('signature', 'Assinatura do beneficiário / paciente', 'assinatura_paciente', 'assinaturas', true),
            $f('signature', 'Assinatura do responsável legal (se aplicável)', 'assinatura_responsavel', 'assinaturas', false),
            $f('signature', 'Assinatura do(a) cirurgião(ã)-dentista', 'assinatura_profissional', 'assinaturas', true),
            $f('text', 'Local', 'local_assinatura', 'assinaturas', false),
        ];
    }
}
