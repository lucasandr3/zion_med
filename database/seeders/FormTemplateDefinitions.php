<?php

namespace Database\Seeders;

class FormTemplateDefinitions
{
    /** @return array<int, array{name: string, description: string, category: string, fields: array}> */
    public static function all(): array
    {
        return array_merge(
            self::geral(),
            self::clinicaMedica(),
            self::odontologia(),
            self::estetica(),
            self::fisioterapia(),
            self::psicologia(),
            self::pediatria(),
            self::ginecologia(),
            self::oftalmologia(),
            self::dermatologia(),
            self::laboratorio()
        );
    }

    private static function field(string $type, string $label, string $nameKey, int $order, bool $required = true, ?array $options = null): array
    {
        $f = ['type' => $type, 'label' => $label, 'name_key' => $nameKey, 'sort_order' => $order, 'required' => $required];
        if ($options !== null) {
            $f['options'] = $options;
        }
        return $f;
    }

    private static function geral(): array
    {
        return [
            [
                'name' => 'Cadastro do Paciente (Básico)',
                'description' => 'Cadastro básico do paciente.',
                'category' => 'geral',
                'fields' => [
                    self::field('text', 'Nome completo', 'nome_completo', 1),
                    self::field('date', 'Data de nascimento', 'data_nascimento', 2),
                    self::field('text', 'CPF', 'cpf', 3, false),
                    self::field('text', 'Telefone', 'telefone', 4, false),
                    self::field('textarea', 'Endereço', 'endereco', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Anamnese (Básica)',
                'description' => 'Questionário de anamnese básica para primeiros atendimentos.',
                'category' => 'geral',
                'fields' => [
                    self::field('text', 'Nome completo', 'nome_completo', 1),
                    self::field('date', 'Data de nascimento', 'data_nascimento', 2),
                    self::field('textarea', 'Queixa principal', 'queixa_principal', 3),
                    self::field('textarea', 'Histórico de doenças atuais', 'historico_doencas', 4, false),
                    self::field('select', 'Possui alergia a medicamentos?', 'alergia_medicamentos', 5, true, ['Não', 'Sim']),
                    self::field('textarea', 'Se sim, quais?', 'quais_alergias', 6, false),
                    self::field('signature', 'Assinatura do paciente', 'assinatura', 7),
                ],
            ],
            [
                'name' => 'Termo de Consentimento (Atendimento/Procedimento)',
                'description' => 'Termo de consentimento livre e esclarecido para procedimentos.',
                'category' => 'geral',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Procedimento a ser realizado', 'procedimento', 3),
                    self::field('radio', 'Declaro ter sido informado sobre os riscos e benefícios', 'declaracao_informado', 4, true, ['Concordo', 'Discordo']),
                    self::field('checkbox', 'Autorizo a realização do procedimento', 'autorizacao', 5),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Triagem (Sinais Vitais)',
                'description' => 'Registro de sinais vitais na triagem.',
                'category' => 'geral',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('number', 'Pressão arterial (mmHg)', 'pa', 3, false),
                    self::field('number', 'Frequência cardíaca (bpm)', 'fc', 4, false),
                    self::field('number', 'Temperatura (°C)', 'temperatura', 5, false),
                    self::field('number', 'Peso (kg)', 'peso', 6, false),
                    self::field('textarea', 'Queixa / Observações', 'observacoes', 7, false),
                    self::field('signature', 'Responsável', 'assinatura', 8),
                ],
            ],
            [
                'name' => 'Checklist de Sala (Abertura/Fechamento)',
                'description' => 'Checklist diário de preparação e higienização da sala.',
                'category' => 'geral',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Responsável', 'responsavel', 2),
                    self::field('checkbox', 'Sala limpa e organizada', 'sala_limpa', 3),
                    self::field('checkbox', 'Equipamentos esterilizados', 'equipamentos_esterilizados', 4),
                    self::field('checkbox', 'Material de consumo conferido', 'material_conferido', 5),
                    self::field('select', 'Tipo', 'tipo', 6, false, ['Abertura', 'Fechamento']),
                    self::field('textarea', 'Observações', 'observacoes', 7, false),
                    self::field('signature', 'Assinatura', 'assinatura', 8),
                ],
            ],
            [
                'name' => 'Ocorrência / Incidente (registro interno)',
                'description' => 'Registro de ocorrências e incidentes internos.',
                'category' => 'geral',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Responsável pelo registro', 'responsavel', 2),
                    self::field('textarea', 'Descrição da ocorrência/incidente', 'descricao', 3),
                    self::field('textarea', 'Medidas tomadas', 'medidas_tomadas', 4, false),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Solicitação Interna (Manutenção/Reposição/TI)',
                'description' => 'Solicitação interna para manutenção, reposição ou TI.',
                'category' => 'geral',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Solicitante', 'solicitante', 2),
                    self::field('select', 'Tipo', 'tipo', 3, true, ['Manutenção', 'Reposição', 'TI']),
                    self::field('textarea', 'Descrição / Itens', 'descricao', 4),
                    self::field('select', 'Prioridade', 'prioridade', 5, false, ['Baixa', 'Média', 'Alta', 'Urgente']),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
            [
                'name' => 'Pesquisa de Satisfação (NPS + comentários)',
                'description' => 'Pesquisa NPS e comentários sobre o atendimento.',
                'category' => 'geral',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('number', 'Nota NPS (0 a 10)', 'nps', 2, true),
                    self::field('textarea', 'Comentários', 'comentarios', 3, false),
                    self::field('text', 'Nome (opcional)', 'nome_opcional', 4, false),
                ],
            ],
        ];
    }

    private static function clinicaMedica(): array
    {
        return [
            [
                'name' => 'Solicitação de Exames',
                'description' => 'Solicitação de exames (tipo, urgência, observações).',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exames solicitados', 'exames', 3),
                    self::field('select', 'Urgência', 'urgencia', 4, false, ['Rotina', 'Urgente']),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                    self::field('signature', 'Médico', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Retorno / Evolução',
                'description' => 'Registro de retorno: queixa, evolução, conduta, retorno em X dias.',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Queixa', 'queixa', 3, false),
                    self::field('textarea', 'Evolução', 'evolucao', 4),
                    self::field('textarea', 'Conduta', 'conduta', 5),
                    self::field('number', 'Retorno em (dias)', 'retorno_dias', 6, false),
                    self::field('signature', 'Assinatura', 'assinatura', 7),
                ],
            ],
            [
                'name' => 'Atestado / Declaração',
                'description' => 'Atestado ou declaração (tipo, período, observações).',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('select', 'Tipo', 'tipo', 3, true, ['Atestado médico', 'Declaração de comparecimento', 'Outro']),
                    self::field('text', 'Período / Dias', 'periodo', 4, false),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Termo de Recusa (exame/procedimento)',
                'description' => 'Registro de recusa do paciente a exame ou procedimento.',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exame/Procedimento recusado', 'procedimento_recusado', 3),
                    self::field('textarea', 'Orientações dadas', 'orientacoes', 4, false),
                    self::field('signature', 'Assinatura do paciente', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Requisição de Encaminhamento',
                'description' => 'Encaminhamento para especialidade (motivo, prioridade).',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('text', 'Especialidade', 'especialidade', 3),
                    self::field('textarea', 'Motivo / Justificativa', 'motivo', 4),
                    self::field('select', 'Prioridade', 'prioridade', 5, false, ['Normal', 'Alta', 'Urgente']),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Controle de Vacinas (simples)',
                'description' => 'Controle de vacinas: vacina, lote, data, próxima dose.',
                'category' => 'clinica_medica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('text', 'Vacina', 'vacina', 2),
                    self::field('text', 'Lote', 'lote', 3, false),
                    self::field('date', 'Data da dose', 'data_dose', 4),
                    self::field('date', 'Próxima dose', 'proxima_dose', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
        ];
    }

    private static function odontologia(): array
    {
        return [
            [
                'name' => 'Anamnese Odontológica',
                'description' => 'Anamnese odontológica (dor, sangramento gengival, bruxismo, alergias).',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('select', 'Dor ou desconforto?', 'dor', 2, false, ['Não', 'Sim']),
                    self::field('select', 'Sangramento gengival?', 'sangramento_gengival', 3, false, ['Não', 'Sim']),
                    self::field('select', 'Bruxismo?', 'bruxismo', 4, false, ['Não', 'Sim']),
                    self::field('textarea', 'Alergias', 'alergias', 5, false),
                    self::field('textarea', 'Outras informações', 'observacoes', 6, false),
                    self::field('signature', 'Assinatura', 'assinatura', 7),
                ],
            ],
            [
                'name' => 'Odontograma Simplificado',
                'description' => 'Odontograma simplificado: seleção e observações (MVP sem desenho).',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Elementos / Dentes envolvidos', 'elementos', 3, false),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Orçamento de Tratamento',
                'description' => 'Orçamento com itens, valor e forma de pagamento.',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Itens do tratamento', 'itens', 3),
                    self::field('text', 'Valor total', 'valor_total', 4, false),
                    self::field('textarea', 'Forma de pagamento', 'forma_pagamento', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Termo de Clareamento',
                'description' => 'Termo de clareamento: orientações e riscos.',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Li e entendi as orientações e riscos', 'ciencia', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Termo de Cirurgia/Extração',
                'description' => 'Termo de cirurgia ou extração: riscos e cuidados.',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Procedimento', 'procedimento', 3),
                    self::field('checkbox', 'Li e entendi os riscos e cuidados', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Pós-operatório',
                'description' => 'Checklist de orientações pós-operatórias entregues.',
                'category' => 'odontologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Orientações de higiene entregues', 'orientacao_higiene', 3),
                    self::field('checkbox', 'Orientações de alimentação entregues', 'orientacao_alimentacao', 4),
                    self::field('checkbox', 'Medicação orientada', 'medicacao', 5),
                    self::field('textarea', 'Outras orientações', 'outras', 6, false),
                    self::field('signature', 'Assinatura', 'assinatura', 7),
                ],
            ],
            [
                'name' => 'Controle de Esterilização',
                'description' => 'Controle de esterilização: data, responsável, autoclave OK/Não OK.',
                'category' => 'odontologia',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Responsável', 'responsavel', 2),
                    self::field('select', 'Autoclave OK?', 'autoclave_ok', 3, true, ['Sim', 'Não']),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Controle de Materiais Críticos',
                'description' => 'Controle de materiais críticos (resina, anestésico, validade, reposição).',
                'category' => 'odontologia',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Material', 'material', 2),
                    self::field('text', 'Lote', 'lote', 3, false),
                    self::field('date', 'Validade', 'validade', 4, false),
                    self::field('select', 'Necessita reposição?', 'reposicao', 5, false, ['Não', 'Sim']),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
        ];
    }

    private static function estetica(): array
    {
        return [
            [
                'name' => 'Anamnese Estética',
                'description' => 'Anamnese estética: procedimentos prévios, alergias, gestante/lactante.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('textarea', 'Procedimentos estéticos prévios', 'procedimentos_previos', 2, false),
                    self::field('textarea', 'Alergias', 'alergias', 3, false),
                    self::field('select', 'Gestante ou lactante?', 'gestante_lactante', 4, true, ['Não', 'Sim']),
                    self::field('textarea', 'Doenças / medicamentos em uso', 'doencas_medicamentos', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Termo de Consentimento – Procedimentos Estéticos',
                'description' => 'Termo de consentimento: riscos e resultados variáveis.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Procedimento', 'procedimento', 3),
                    self::field('checkbox', 'Li e entendi os riscos e variabilidade dos resultados', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Registro Fotográfico',
                'description' => 'Upload de fotos antes/depois e autorização de uso de imagem.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('select', 'Momento', 'momento', 3, false, ['Antes', 'Depois', 'Acompanhamento']),
                    self::field('checkbox', 'Autorizo uso de imagem para fins clínicos/registro', 'autorizacao_imagem', 4),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Ficha do Procedimento',
                'description' => 'Produto, lote, área, quantidade, profissional.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('text', 'Produto', 'produto', 3),
                    self::field('text', 'Lote', 'lote', 4, false),
                    self::field('text', 'Área tratada', 'area', 5, false),
                    self::field('text', 'Quantidade', 'quantidade', 6, false),
                    self::field('text', 'Profissional', 'profissional', 7, false),
                    self::field('textarea', 'Observações', 'observacoes', 8, false),
                ],
            ],
            [
                'name' => 'Orientações Pós-procedimento',
                'description' => 'Checklist de orientações entregues e ciência.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Orientações entregues e paciente ciente', 'ciencia', 3),
                    self::field('textarea', 'Orientações específicas', 'orientacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Acompanhamento 7/15/30 dias',
                'description' => 'Acompanhamento: sintomas, satisfação, fotos.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data do acompanhamento', 'data', 2),
                    self::field('select', 'Dias pós-procedimento', 'dias_pos', 3, false, ['7 dias', '15 dias', '30 dias']),
                    self::field('textarea', 'Sintomas / Queixas', 'sintomas', 4, false),
                    self::field('number', 'Satisfação (1-10)', 'satisfacao', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
            [
                'name' => 'Termo de Recusa / Contraindicação',
                'description' => 'Registro quando o procedimento não é realizado.',
                'category' => 'estetica',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Motivo da recusa/contraindicação', 'motivo', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Pesquisa de Satisfação Estética',
                'description' => 'Expectativa vs resultado percebido.',
                'category' => 'estetica',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('number', 'Expectativa atendida (1-10)', 'expectativa', 2, false),
                    self::field('number', 'Resultado percebido (1-10)', 'resultado', 3, false),
                    self::field('textarea', 'Comentários', 'comentarios', 4, false),
                ],
            ],
        ];
    }

    private static function fisioterapia(): array
    {
        return [
            [
                'name' => 'Avaliação Fisioterapêutica',
                'description' => 'Avaliação: dor, mobilidade, testes, limitações.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Queixa principal / Dor', 'queixa_dor', 3),
                    self::field('textarea', 'Mobilidade e testes', 'mobilidade_testes', 4, false),
                    self::field('textarea', 'Limitações', 'limitacoes', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Plano de Tratamento',
                'description' => 'Objetivos, frequência, duração, exercícios.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Objetivos', 'objetivos', 3),
                    self::field('text', 'Frequência e duração', 'frequencia_duracao', 4, false),
                    self::field('textarea', 'Exercícios / Conduta', 'exercicios', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Evolução por Sessão',
                'description' => 'Dor antes/depois, técnicas, observações.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data da sessão', 'data', 2),
                    self::field('number', 'Dor antes (0-10)', 'dor_antes', 3, false),
                    self::field('number', 'Dor depois (0-10)', 'dor_depois', 4, false),
                    self::field('textarea', 'Técnicas utilizadas', 'tecnicas', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                    self::field('signature', 'Assinatura', 'assinatura', 7),
                ],
            ],
            [
                'name' => 'Termo de Consentimento – Terapia Manual/Exercícios',
                'description' => 'Consentimento para terapia manual e exercícios.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Autorizo terapia manual e exercícios', 'ciencia', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Termo de Uso de Equipamentos (TENS/ultrassom)',
                'description' => 'Consentimento para uso de equipamentos.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Equipamentos a serem utilizados', 'equipamentos', 3, false),
                    self::field('checkbox', 'Li e autorizo o uso', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Orientações Domiciliares',
                'description' => 'Exercícios prescritos para casa.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exercícios prescritos', 'exercicios', 3),
                    self::field('textarea', 'Frequência e cuidados', 'frequencia_cuidados', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Escala de Dor (VAS)',
                'description' => 'Escala visual analógica 0–10 e observações.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('number', 'Dor (0-10)', 'dor_vas', 3, true),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                ],
            ],
            [
                'name' => 'Reavaliação',
                'description' => 'Reavaliação a cada X sessões.',
                'category' => 'fisioterapia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('number', 'Número da sessão', 'numero_sessao', 3, false),
                    self::field('textarea', 'Reavaliação', 'reavaliacao', 4),
                    self::field('textarea', 'Conduta', 'conduta', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
        ];
    }

    private static function psicologia(): array
    {
        return [
            [
                'name' => 'Cadastro + Consentimento LGPD',
                'description' => 'Informações e aceite de uso de dados (LGPD).',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome completo', 'nome_completo', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Autorizo o uso dos dados conforme política de privacidade', 'consentimento_lgpd', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Contrato Terapêutico',
                'description' => 'Regras, faltas, pagamentos (simples).',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Regras e combinados', 'regras', 3, false),
                    self::field('textarea', 'Faltas e pagamentos', 'faltas_pagamentos', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Anamnese Inicial (Psicologia)',
                'description' => 'Histórico, queixa, objetivos.',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Histórico relevante', 'historico', 3, false),
                    self::field('textarea', 'Queixa principal', 'queixa', 4),
                    self::field('textarea', 'Objetivos do atendimento', 'objetivos', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Triagem / Avaliação Inicial (objetiva)',
                'description' => 'Avaliação inicial objetiva (MVP sem campos sensíveis).',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Demanda relatada', 'demanda', 3, false),
                    self::field('textarea', 'Observações objetivas', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Registro de Sessão (resumo)',
                'description' => 'Resumo mínimo e protegido por perfil.',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data da sessão', 'data', 2),
                    self::field('textarea', 'Resumo da sessão', 'resumo', 3, false),
                    self::field('textarea', 'Encaminhamentos / tarefas', 'encaminhamentos', 4, false),
                ],
            ],
            [
                'name' => 'Escalas Simples (humor, ansiedade 1–5)',
                'description' => 'Escalas simples: humor, ansiedade 1-5.',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('number', 'Humor (1-5)', 'humor', 3, false),
                    self::field('number', 'Ansiedade (1-5)', 'ansiedade', 4, false),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                ],
            ],
            [
                'name' => 'Termo para Atendimento de Menor',
                'description' => 'Responsável e autorização para atendimento de menor.',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do menor', 'nome_menor', 1),
                    self::field('text', 'Nome do responsável', 'nome_responsavel', 2),
                    self::field('date', 'Data', 'data', 3),
                    self::field('checkbox', 'Autorizo o atendimento do menor', 'autorizacao', 4),
                    self::field('signature', 'Assinatura do responsável', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Encaminhamento (para outro profissional/serviço)',
                'description' => 'Encaminhamento para outro profissional ou serviço.',
                'category' => 'psicologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Destino / Motivo', 'destino_motivo', 3),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
        ];
    }

    private static function pediatria(): array
    {
        return [
            [
                'name' => 'Cadastro do Responsável + Autorização',
                'description' => 'Cadastro do responsável e autorização para atendimento.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome do responsável', 'nome_responsavel', 1),
                    self::field('text', 'CPF', 'cpf', 2, false),
                    self::field('text', 'Telefone', 'telefone', 3, false),
                    self::field('checkbox', 'Autorizo o atendimento da criança', 'autorizacao', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Anamnese Pediátrica',
                'description' => 'Gestação, parto, alergias, desenvolvimento.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('textarea', 'Gestação e parto', 'gestacao_parto', 2, false),
                    self::field('textarea', 'Alergias', 'alergias', 3, false),
                    self::field('textarea', 'Desenvolvimento', 'desenvolvimento', 4, false),
                    self::field('textarea', 'Queixa atual', 'queixa', 5, false),
                    self::field('signature', 'Assinatura do responsável', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Controle de Vacinação',
                'description' => 'Dose, lote, próxima dose.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('text', 'Vacina', 'vacina', 2),
                    self::field('text', 'Dose / Lote', 'dose_lote', 3, false),
                    self::field('date', 'Data', 'data_dose', 4),
                    self::field('date', 'Próxima dose', 'proxima_dose', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
            [
                'name' => 'Curva de Crescimento (simples)',
                'description' => 'Altura, peso, perímetro cefálico.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('number', 'Altura (cm)', 'altura', 3, false),
                    self::field('number', 'Peso (kg)', 'peso', 4, false),
                    self::field('number', 'Perímetro cefálico (cm)', 'perimetro_cefalico', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
            [
                'name' => 'Termo de Consentimento – Procedimentos',
                'description' => 'Consentimento para procedimentos em criança.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('text', 'Nome do responsável', 'nome_responsavel', 2),
                    self::field('date', 'Data', 'data', 3),
                    self::field('textarea', 'Procedimento', 'procedimento', 4),
                    self::field('checkbox', 'Autorizo o procedimento', 'autorizacao', 5),
                    self::field('signature', 'Assinatura do responsável', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Orientações Pós-consulta',
                'description' => 'Orientações entregues após consulta.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Orientações', 'orientacoes', 3),
                    self::field('signature', 'Assinatura do responsável', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Termo de Recusa de Vacina/Procedimento',
                'description' => 'Registro de recusa de vacina ou procedimento.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('text', 'Nome do responsável', 'nome_responsavel', 2),
                    self::field('date', 'Data', 'data', 3),
                    self::field('textarea', 'Vacina/Procedimento recusado', 'recusado', 4),
                    self::field('signature', 'Assinatura do responsável', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Declaração de Comparecimento',
                'description' => 'Declaração de comparecimento à consulta.',
                'category' => 'pediatria',
                'fields' => [
                    self::field('text', 'Nome da criança', 'nome_crianca', 1),
                    self::field('text', 'Nome do responsável', 'nome_responsavel', 2),
                    self::field('date', 'Data do comparecimento', 'data', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
        ];
    }

    private static function ginecologia(): array
    {
        return [
            [
                'name' => 'Anamnese Ginecológica',
                'description' => 'Ciclo, contraceptivo, histórico, alergias.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Ciclo menstrual', 'ciclo', 3, false),
                    self::field('textarea', 'Contraceptivo / histórico', 'contraceptivo_historico', 4, false),
                    self::field('textarea', 'Alergias', 'alergias', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Pré-natal (visita)',
                'description' => 'Pressão, peso, exames, queixas.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data da visita', 'data', 2),
                    self::field('number', 'Pressão arterial', 'pa', 3, false),
                    self::field('number', 'Peso (kg)', 'peso', 4, false),
                    self::field('textarea', 'Exames / Queixas', 'exames_queixas', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Termo de Consentimento – Exames/Procedimentos',
                'description' => 'Consentimento para exames e procedimentos.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exame/Procedimento', 'procedimento', 3),
                    self::field('checkbox', 'Autorizo e estou ciente', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Plano de Parto (simples)',
                'description' => 'Preferências e observações para o parto.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Preferências', 'preferencias', 3, false),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Orientações de Pré-natal',
                'description' => 'Orientações de pré-natal entregues.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Orientações', 'orientacoes', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Registro de Exames',
                'description' => 'Checklist de exames.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exames solicitados/resultados', 'exames', 3, false),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                ],
            ],
            [
                'name' => 'Termo de Recusa',
                'description' => 'Registro de recusa a exame ou procedimento.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Recusa', 'recusa', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Acompanhamento Pós-parto',
                'description' => 'Registro de acompanhamento pós-parto.',
                'category' => 'ginecologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Evolução / Queixas', 'evolucao', 3, false),
                    self::field('textarea', 'Orientações', 'orientacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
        ];
    }

    private static function oftalmologia(): array
    {
        return [
            [
                'name' => 'Triagem Oftalmo',
                'description' => 'Queixa, uso de lentes, histórico.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Queixa principal', 'queixa', 3),
                    self::field('select', 'Usa lentes/óculos?', 'usa_lentes', 4, false, ['Não', 'Sim']),
                    self::field('textarea', 'Histórico', 'historico', 5, false),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Consentimento – Exame/Procedimento',
                'description' => 'Consentimento para exame ou procedimento oftalmológico.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exame/Procedimento', 'procedimento', 3),
                    self::field('checkbox', 'Autorizo e estou ciente', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Pré-operatório',
                'description' => 'Checklist pré-operatório.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Checklist pré-op conferido', 'checklist_ok', 3),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Pós-operatório',
                'description' => 'Orientações e confirmação pós-operatória.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Orientações entregues', 'orientacoes', 3),
                    self::field('checkbox', 'Paciente ciente', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Acompanhamento 7/30 dias',
                'description' => 'Acompanhamento pós-procedimento.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('select', 'Dias', 'dias', 3, false, ['7 dias', '30 dias']),
                    self::field('textarea', 'Evolução / Queixas', 'evolucao', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Termo de Recusa',
                'description' => 'Registro de recusa.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Recusa', 'recusa', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Solicitação de Exames',
                'description' => 'Solicitação de exames oftalmológicos.',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exames', 'exames', 3),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Pesquisa de Satisfação',
                'description' => 'Pesquisa de satisfação (oftalmologia).',
                'category' => 'oftalmologia',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('number', 'Nota (0-10)', 'nota', 2, false),
                    self::field('textarea', 'Comentários', 'comentarios', 3, false),
                ],
            ],
        ];
    }

    private static function dermatologia(): array
    {
        return [
            [
                'name' => 'Anamnese Dermatológica',
                'description' => 'Histórico, alergias, medicamentos.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Histórico dermatológico', 'historico', 3, false),
                    self::field('textarea', 'Alergias e medicamentos', 'alergias_medicamentos', 4, false),
                    self::field('textarea', 'Queixa principal', 'queixa', 5),
                    self::field('signature', 'Assinatura', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Registro Fotográfico + autorização',
                'description' => 'Fotos e autorização de uso de imagem.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('select', 'Momento', 'momento', 3, false, ['Antes', 'Depois', 'Acompanhamento']),
                    self::field('checkbox', 'Autorizo uso de imagem', 'autorizacao_imagem', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Consentimento – Procedimentos',
                'description' => 'Consentimento para procedimentos dermatológicos.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Procedimento', 'procedimento', 3),
                    self::field('checkbox', 'Autorizo e estou ciente', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Ficha do Procedimento',
                'description' => 'Produto, lote, área.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('text', 'Produto', 'produto', 3),
                    self::field('text', 'Lote', 'lote', 4, false),
                    self::field('text', 'Área', 'area', 5, false),
                    self::field('textarea', 'Observações', 'observacoes', 6, false),
                ],
            ],
            [
                'name' => 'Pós-procedimento',
                'description' => 'Orientações pós-procedimento.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Orientações', 'orientacoes', 3),
                    self::field('checkbox', 'Paciente ciente', 'ciencia', 4),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Acompanhamento',
                'description' => 'Acompanhamento dermatológico.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Evolução / Queixas', 'evolucao', 3, false),
                    self::field('textarea', 'Conduta', 'conduta', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Triagem (sinais e queixa)',
                'description' => 'Triagem com sinais e queixa.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Queixa e sinais', 'queixa_sinais', 3),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                ],
            ],
            [
                'name' => 'Satisfação (resultado percebido)',
                'description' => 'Pesquisa de satisfação com resultado percebido.',
                'category' => 'dermatologia',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('number', 'Resultado percebido (1-10)', 'resultado', 2, false),
                    self::field('textarea', 'Comentários', 'comentarios', 3, false),
                ],
            ],
        ];
    }

    private static function laboratorio(): array
    {
        return [
            [
                'name' => 'Ficha de Coleta',
                'description' => 'Paciente, exames, jejum, horário.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('textarea', 'Exames', 'exames', 3),
                    self::field('select', 'Jejum?', 'jejum', 4, false, ['Não', 'Sim']),
                    self::field('text', 'Horário da coleta', 'horario', 5, false),
                    self::field('signature', 'Responsável', 'assinatura', 6),
                ],
            ],
            [
                'name' => 'Checklist de Coleta',
                'description' => 'Tubos, identificação, responsável.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Responsável', 'responsavel', 2),
                    self::field('checkbox', 'Tubos e identificação conferidos', 'tubos_ok', 3),
                    self::field('textarea', 'Observações', 'observacoes', 4, false),
                    self::field('signature', 'Assinatura', 'assinatura', 5),
                ],
            ],
            [
                'name' => 'Termo de Consentimento – Coleta',
                'description' => 'Consentimento para coleta.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data', 'data', 2),
                    self::field('checkbox', 'Autorizo a coleta e estou ciente', 'ciencia', 3),
                    self::field('signature', 'Assinatura', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Registro de Não Conformidade',
                'description' => 'Amostra inadequada, re-coleta.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Paciente / Amostra', 'paciente_amostra', 2),
                    self::field('textarea', 'Não conformidade', 'nao_conformidade', 3),
                    self::field('select', 'Re-coleta?', 'recoleta', 4, false, ['Não', 'Sim']),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                ],
            ],
            [
                'name' => 'Controle de Temperatura',
                'description' => 'Geladeira/ambiente.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Equipamento / Ambiente', 'equipamento', 2),
                    self::field('number', 'Temperatura (°C)', 'temperatura', 3, false),
                    self::field('text', 'Responsável', 'responsavel', 4, false),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                ],
            ],
            [
                'name' => 'Controle de Estoque de Materiais',
                'description' => 'Controle de estoque.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('text', 'Material', 'material', 2),
                    self::field('text', 'Quantidade / Validade', 'quantidade_validade', 3, false),
                    self::field('select', 'Reposição necessária?', 'reposicao', 4, false, ['Não', 'Sim']),
                    self::field('textarea', 'Observações', 'observacoes', 5, false),
                ],
            ],
            [
                'name' => 'Entrega de Resultado',
                'description' => 'Confirmação de entrega do resultado.',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_paciente', 1),
                    self::field('date', 'Data da entrega', 'data', 2),
                    self::field('checkbox', 'Resultado entregue e explicado', 'entrega_ok', 3),
                    self::field('signature', 'Assinatura (paciente ou responsável)', 'assinatura', 4),
                ],
            ],
            [
                'name' => 'Pesquisa de Satisfação',
                'description' => 'Pesquisa de satisfação (laboratório).',
                'category' => 'laboratorio',
                'fields' => [
                    self::field('date', 'Data', 'data', 1),
                    self::field('number', 'Nota (0-10)', 'nota', 2, false),
                    self::field('textarea', 'Comentários', 'comentarios', 3, false),
                ],
            ],
        ];
    }
}
