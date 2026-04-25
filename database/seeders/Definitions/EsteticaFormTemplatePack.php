<?php

declare(strict_types=1);

namespace Database\Seeders\Definitions;

/**
 * Modelos padrão do nicho Estética (pastas: Cadastro e Documentação, Anamneses, Acompanhamento e Controle).
 *
 * @phpstan-type FieldDef array{type: string, label: string, name_key: string, sort_order: int, required?: bool, options?: array<int, string>}
 */
final class EsteticaFormTemplatePack
{
    /** @param array<int, string>|null $options */
    private static function field(string $type, string $label, string $nameKey, int $order, bool $required = true, ?array $options = null): array
    {
        $f = ['type' => $type, 'label' => $label, 'name_key' => $nameKey, 'sort_order' => $order, 'required' => $required];
        if ($options !== null) {
            $f['options'] = $options;
        }

        return $f;
    }

    /**
     * @return array<int, array{name: string, description: string, category: string, fields: array<int, FieldDef>}>
     */
    public static function templates(): array
    {
        return [
            self::cadastroPaciente(),
            self::tcleConsentimento(),
            self::autorizacaoImagemLgpd(),
            self::orcamentoPlanoTratamento(),
            self::anamneseGeral(),
            self::anamneseToxinaBotulinica(),
            self::anamneseAcidoHialuronico(),
            self::anamneseLaserPeeling(),
            self::anamneseDepilacaoLaser(),
            self::anamneseTratamentosCorporais(),
            self::evolucaoSessao(),
            self::fichaRetornoPosTratamento(),
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function cadastroPaciente(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Ficha de Cadastro do Paciente',
            'description' => 'Cadastro completo: dados pessoais, contato, origem, plano de saúde e LGPD.',
            'category' => 'cadastro_documentacao',
            'fields' => [
                $f('text', 'Número da ficha', 'ficha_numero', false),
                $f('date', 'Data', 'data', false),
                $f('text', 'Nome completo', 'nome_completo'),
                $f('date', 'Data de nascimento', 'data_nascimento'),
                $f('text', 'Idade', 'idade', false),
                $f('radio', 'Sexo', 'sexo', true, ['Feminino', 'Masculino', 'Outro']),
                $f('text', 'CPF', 'cpf', false),
                $f('text', 'RG', 'rg', false),
                $f('select', 'Estado civil', 'estado_civil', false, ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União estável']),
                $f('text', 'Profissão', 'profissao', false),
                $f('text', 'Indicado por', 'indicado_por', false),
                $f('text', 'Telefone / WhatsApp', 'telefone_whatsapp', false),
                $f('text', 'Telefone alternativo', 'telefone_alternativo', false),
                $f('text', 'E-mail', 'email', false),
                $f('text', 'Endereço completo', 'endereco_completo', false),
                $f('text', 'Bairro', 'bairro', false),
                $f('text', 'Cidade', 'cidade', false),
                $f('text', 'CEP', 'cep', false),
                $f('checkbox', 'Como nos conheceu: Instagram', 'conheceu_instagram', false),
                $f('checkbox', 'Como nos conheceu: Google', 'conheceu_google', false),
                $f('checkbox', 'Como nos conheceu: Facebook', 'conheceu_facebook', false),
                $f('checkbox', 'Como nos conheceu: Indicação de amigo/familiar', 'conheceu_indicacao_amigo', false),
                $f('checkbox', 'Como nos conheceu: Indicação médica', 'conheceu_indicacao_medica', false),
                $f('checkbox', 'Como nos conheceu: Plano de saúde', 'conheceu_plano_saude', false),
                $f('text', 'Como nos conheceu: Outro (especificar)', 'conheceu_outro', false),
                $f('radio', 'Possui plano de saúde?', 'possui_plano_saude', false, ['Sim', 'Não']),
                $f('text', 'Operadora do plano', 'operadora_plano', false),
                $f('text', 'Número da carteirinha', 'numero_carteirinha', false),
                $f('checkbox', 'LGPD: aceito receber comunicações e promoções por WhatsApp/e-mail', 'lgpd_comunicacoes', false),
                $f('checkbox', 'LGPD: aceito receber lembretes de consulta', 'lgpd_lembretes', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Responsável legal (se menor)', 'assinatura_responsavel_legal', false),
                $f('signature', 'Data / conferência', 'assinatura_data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function tcleConsentimento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'TCLE — Termo de Consentimento Livre e Esclarecido',
            'description' => 'Identificação, procedimento, riscos, fotos, declarações e orientações pós-procedimento.',
            'category' => 'cadastro_documentacao',
            'fields' => [
                $f('text', 'Nome do paciente', 'nome_paciente'),
                $f('date', 'Data de nascimento', 'data_nascimento', false),
                $f('text', 'CPF', 'cpf', false),
                $f('text', 'Procedimento(s) a ser(em) realizado(s)', 'procedimentos'),
                $f('text', 'Profissional responsável', 'profissional_responsavel', false),
                $f('date', 'Data do procedimento', 'data_procedimento', false),
                $f('textarea', 'Descrição específica do procedimento e técnica utilizada', 'descricao_procedimento_tecnica', false),
                $f('textarea', 'Benefícios esperados', 'beneficios_esperados', false),
                $f('textarea', 'Riscos e efeitos colaterais possíveis', 'riscos_efeitos_colaterais', false),
                $f('text', 'Número de sessões previstas', 'numero_sessoes_previstas', false),
                $f('text', 'Intervalo entre sessões', 'intervalo_sessoes', false),
                $f('text', 'Valor do procedimento / pacote', 'valor_procedimento', false),
                $f('checkbox', 'Autorizo registro fotográfico exclusivamente para documentação clínica', 'foto_doc_clinica', false),
                $f('checkbox', 'Autorizo uso de imagens sem identificação (educação/marketing)', 'foto_sem_id', false),
                $f('checkbox', 'Não autorizo uso de imagens além da documentação clínica', 'foto_nao_autorizo', false),
                $f('checkbox', 'Declaro que li e compreendi as informações e minhas dúvidas foram respondidas', 'decl_leitura', false),
                $f('checkbox', 'Declaro que as informações da anamnese/cadastro são verdadeiras', 'decl_verdade', false),
                $f('checkbox', 'Estou ciente de que posso revogar o consentimento a qualquer momento', 'decl_revogacao', false),
                $f('checkbox', 'Autorizo a realização do(s) procedimento(s) de forma livre e esclarecida', 'decl_autorizo_proc', false),
                $f('checkbox', 'Declaro ciência dos cuidados pós-procedimento e compromisso de seguí-los', 'decl_pos', false),
                $f('textarea', 'Orientações pós-procedimento (preenchimento profissional)', 'orientacoes_pos', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Responsável legal (se menor de 18 anos)', 'assinatura_responsavel', false),
                $f('signature', 'Profissional responsável', 'assinatura_profissional', false),
                $f('text', 'Data e local', 'data_local', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function autorizacaoImagemLgpd(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Autorização de Imagem e Proteção de Dados (LGPD)',
            'description' => 'Titular, consentimentos LGPD, direitos do titular e autorização de uso de imagem.',
            'category' => 'cadastro_documentacao',
            'fields' => [
                $f('text', 'Nome completo', 'nome_completo'),
                $f('text', 'CPF', 'cpf', false),
                $f('date', 'Data de nascimento', 'data_nascimento', false),
                $f('text', 'E-mail', 'email', false),
                $f('text', 'Telefone / WhatsApp', 'telefone', false),
                $f('checkbox', 'LGPD: autorizo armazenamento e uso dos dados para atendimento clínico', 'lgpd_atendimento', false),
                $f('checkbox', 'LGPD: autorizo comunicações de marketing (opcional)', 'lgpd_marketing', false),
                $f('checkbox', 'LGPD: autorizo compartilhamento com parceiros clínicos quando necessário (opcional)', 'lgpd_parceiros', false),
                $f('radio', 'Imagem: registro fotográfico apenas para fins clínicos exclusivos', 'img_clinica_exclusivo', false, ['Sim', 'Não se aplica']),
                $f('checkbox', 'Imagem: autorizo uso em materiais educacionais/científicos sem identificação', 'img_educacao', false),
                $f('checkbox', 'Imagem: autorizo uso em redes sociais/marketing sem identificação', 'img_redes_sem_id', false),
                $f('checkbox', 'Imagem: autorizo uso com identificação (depoimentos)', 'img_com_identificacao', false),
                $f('radio', 'Imagem: não autorizo qualquer uso além da documentação clínica', 'img_nao_autorizo_extra', false, ['Sim', 'Não']),
                $f('signature', 'Assinatura do titular / paciente', 'assinatura_titular', false),
                $f('signature', 'Responsável legal (se menor de 18 anos)', 'assinatura_responsavel', false),
                $f('signature', 'Encarregado de dados / responsável clínica', 'assinatura_encarregado', false),
                $f('text', 'Data e local', 'data_local', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function orcamentoPlanoTratamento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);
        $fields = [
            $f('text', 'Número do orçamento', 'orcamento_numero', false),
            $f('date', 'Data do orçamento', 'data_orcamento', false),
            $f('text', 'Nome do paciente', 'nome_paciente'),
            $f('text', 'CPF', 'cpf', false),
            $f('text', 'Telefone', 'telefone', false),
            $f('text', 'Profissional responsável', 'profissional_responsavel', false),
            $f('date', 'Validade deste orçamento', 'validade_orcamento', false),
        ];
        for ($i = 1; $i <= 6; $i++) {
            $fields[] = $f('text', "Procedimento / serviço (linha {$i})", "orc_linha{$i}_procedimento", false);
            $fields[] = $f('text', "Sessões (linha {$i})", "orc_linha{$i}_sessoes", false);
            $fields[] = $f('text', "Valor unitário (linha {$i})", "orc_linha{$i}_valor_unit", false);
            $fields[] = $f('text', "Desconto (linha {$i})", "orc_linha{$i}_desconto", false);
            $fields[] = $f('text', "Subtotal (linha {$i})", "orc_linha{$i}_subtotal", false);
        }
        $fields = array_merge($fields, [
            $f('text', 'Desconto geral aplicado', 'desconto_geral', false),
            $f('text', 'Total do tratamento', 'total_tratamento', false),
            $f('text', 'Cartão de crédito — parcelamento disponível', 'pag_cartao_parcelas', false),
            $f('text', 'PIX / transferência — desconto à vista', 'pag_pix_desconto', false),
            $f('text', 'Dinheiro — desconto à vista', 'pag_dinheiro_desconto', false),
            $f('select', 'Forma de pagamento escolhida', 'forma_pagamento', false, [
                'PIX à vista', 'Dinheiro à vista', 'Cartão de débito', 'Cartão de crédito à vista', 'Cartão de crédito parcelado', 'Combinado / misto',
            ]),
            $f('text', 'Parcelas / observação', 'parcelas_obs', false),
            $f('text', 'Valor da entrada', 'valor_entrada', false),
            $f('date', 'Data do primeiro pagamento', 'data_primeiro_pagamento', false),
            $f('select', 'Frequência das sessões', 'frequencia_sessoes', false, [
                '1x por semana', '2x por semana', 'A cada 15 dias', '1x por mês', 'Outra frequência',
            ]),
            $f('text', 'Duração estimada do tratamento', 'duracao_estimada', false),
            $f('date', 'Data de início prevista', 'data_inicio_prevista', false),
            $f('textarea', 'Observações sobre o plano', 'observacoes_plano', false),
            $f('checkbox', 'Aceite: li e aceito as condições deste orçamento e plano', 'aceite_condicoes', false),
            $f('checkbox', 'Aceite: ciente de que pacote garante sessões, não resultado específico', 'aceite_pacote', false),
            $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
            $f('signature', 'Responsável comercial / clínico', 'assinatura_responsavel_comercial', false),
            $f('signature', 'Data (confirmação)', 'assinatura_data', false),
        ]);

        return [
            'name' => 'Orçamento e Plano de Tratamento',
            'description' => 'Itens, valores, pagamento, plano de sessões e aceite.',
            'category' => 'cadastro_documentacao',
            'fields' => $fields,
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseGeral(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Anamnese Geral',
            'description' => 'Queixa, histórico de saúde, medicamentos, alergias, gestação, hábitos, pele e observações.',
            'category' => 'anamneses',
            'fields' => [
                $f('textarea', 'Qual o motivo da sua consulta hoje?', 'motivo_consulta'),
                $f('textarea', 'Quais resultados você espera alcançar com o tratamento?', 'resultados_esperados', false),
                $f('radio', 'Já realizou tratamentos estéticos anteriores?', 'tratamentos_anteriores', false, ['Sim', 'Não']),
                $f('text', 'Se sim, quais e quando?', 'tratamentos_anteriores_detalhe', false),
                $f('checkbox', 'Histórico: Diabetes', 'hist_diabetes', false),
                $f('checkbox', 'Histórico: Hipertensão', 'hist_hipertensao', false),
                $f('checkbox', 'Histórico: Hipotensão', 'hist_hipotensao', false),
                $f('checkbox', 'Histórico: Doenças cardíacas', 'hist_cardiacas', false),
                $f('checkbox', 'Histórico: Doenças autoimunes', 'hist_autoimunes', false),
                $f('checkbox', 'Histórico: Distúrbios da tireóide', 'hist_tireoide', false),
                $f('checkbox', 'Histórico: Epilepsia', 'hist_epilepsia', false),
                $f('checkbox', 'Histórico: Transtornos de coagulação', 'hist_coagulacao', false),
                $f('checkbox', 'Histórico: Câncer (ativo ou histórico)', 'hist_cancer', false),
                $f('checkbox', 'Histórico: Lúpus', 'hist_lupus', false),
                $f('checkbox', 'Histórico: Psoríase', 'hist_psoriase', false),
                $f('checkbox', 'Histórico: Vitiligo', 'hist_vitiligo', false),
                $f('checkbox', 'Histórico: Rosácea', 'hist_rosacea', false),
                $f('checkbox', 'Histórico: Herpes recorrente', 'hist_herpes', false),
                $f('checkbox', 'Histórico: Queloides / cicatrizes hipertróficas', 'hist_queloides', false),
                $f('checkbox', 'Histórico: Depressão / ansiedade', 'hist_depressao', false),
                $f('checkbox', 'Histórico: HIV / imunossupressão', 'hist_hiv', false),
                $f('checkbox', 'Histórico: Nenhuma das anteriores', 'hist_nenhuma', false),
                $f('text', 'Outras condições de saúde relevantes', 'outras_condicoes', false),
                $f('radio', 'Faz uso de medicamentos contínuos?', 'med_continuo', false, ['Sim', 'Não']),
                $f('text', 'Medicamentos e dosagens', 'med_lista', false),
                $f('checkbox', 'Medicamentos: anticoagulantes', 'med_anticoag', false),
                $f('checkbox', 'Medicamentos: isotretinoína (Roacutan)', 'med_roacutan', false),
                $f('checkbox', 'Medicamentos: corticosteroides', 'med_corticoide', false),
                $f('checkbox', 'Medicamentos: fotossensibilizantes', 'med_fotossens', false),
                $f('checkbox', 'Medicamentos: retinóides tópicos', 'med_retinoide', false),
                $f('checkbox', 'Medicamentos: imunossupressores', 'med_imuno', false),
                $f('checkbox', 'Medicamentos: antidepressivos / ansiolíticos', 'med_psico', false),
                $f('checkbox', 'Medicamentos: anticoncepcionais', 'med_ac', false),
                $f('text', 'Suplementos / vitaminas', 'suplementos', false),
                $f('radio', 'Possui alergia conhecida?', 'alergia_sn', false, ['Sim', 'Não']),
                $f('text', 'Descrição da alergia e reação', 'alergia_desc', false),
                $f('checkbox', 'Alergia: látex', 'alerg_latex', false),
                $f('checkbox', 'Alergia: metais / níquel', 'alerg_metais', false),
                $f('checkbox', 'Alergia: anestésicos tópicos', 'alerg_anestesico', false),
                $f('checkbox', 'Alergia: iodo', 'alerg_iodo', false),
                $f('checkbox', 'Alergia: fragrâncias / perfumes', 'alerg_fragrancia', false),
                $f('checkbox', 'Alergia: ácido hialurônico', 'alerg_ah', false),
                $f('checkbox', 'Alergia: cosméticos específicos', 'alerg_cosmetico', false),
                $f('radio', 'Está gestante?', 'gestante', false, ['Sim', 'Não', 'Possivelmente']),
                $f('radio', 'Está amamentando?', 'amamentando', false, ['Sim', 'Não']),
                $f('date', 'Data da última menstruação', 'dum', false),
                $f('radio', 'Tabagismo', 'tabagismo', false, ['Não', 'Sim', 'Ex-fumante']),
                $f('select', 'Consumo de álcool', 'alcool', false, ['Não consome', 'Ocasionalmente', 'Moderadamente', 'Frequentemente']),
                $f('select', 'Atividade física', 'atividade_fisica', false, ['Sedentário', 'Leve (1-2x/semana)', 'Moderado (3-4x/semana)', 'Intenso (5+x/semana)']),
                $f('select', 'Horas de sono por noite', 'sono', false, ['Menos de 5h', '5 a 6h', '7 a 8h', 'Mais de 8h']),
                $f('select', 'Nível de estresse', 'estresse', false, ['Baixo', 'Moderado', 'Alto', 'Muito alto']),
                $f('select', 'Ingestão de água (litros/dia)', 'agua', false, ['Menos de 1L', '1 a 1,5L', '1,5 a 2L', 'Mais de 2L']),
                $f('radio', 'Exposição solar frequente / atividades ao ar livre', 'exposicao_solar', false, ['Sim', 'Não']),
                $f('radio', 'Classificação da pele', 'tipo_pele', false, ['Seca', 'Oleosa', 'Mista', 'Normal', 'Não sei']),
                $f('radio', 'Pele sensível?', 'pele_sensivel', false, ['Sim', 'Não', 'Às vezes']),
                $f('radio', 'Usa protetor solar diariamente?', 'fps_diario', false, ['Sim', 'Não', 'Às vezes']),
                $f('text', 'Qual FPS utiliza?', 'fps_valor', false),
                $f('textarea', 'Produtos de skincare em uso', 'skincare', false),
                $f('checkbox', 'Queixa cutânea: manchas / melasma', 'queixa_manchas', false),
                $f('checkbox', 'Queixa cutânea: acne ativa', 'queixa_acne', false),
                $f('checkbox', 'Queixa cutânea: cicatrizes de acne', 'queixa_cicatriz_acne', false),
                $f('checkbox', 'Queixa cutânea: flacidez', 'queixa_flacidez', false),
                $f('checkbox', 'Queixa cutânea: rugas / linhas finas', 'queixa_rugas', false),
                $f('checkbox', 'Queixa cutânea: poros dilatados', 'queixa_poros', false),
                $f('checkbox', 'Queixa cutânea: oleosidade excessiva', 'queixa_oleosidade', false),
                $f('checkbox', 'Queixa cutânea: ressecamento', 'queixa_ressecamento', false),
                $f('checkbox', 'Queixa cutânea: olheiras', 'queixa_olheiras', false),
                $f('checkbox', 'Queixa cutânea: vasinhos / eritrose', 'queixa_vasos', false),
                $f('checkbox', 'Queixa cutânea: estrias', 'queixa_estrias', false),
                $f('checkbox', 'Queixa cutânea: celulite', 'queixa_celulite', false),
                $f('checkbox', 'Queixa cutânea: pelos encravados', 'queixa_encravados', false),
                $f('checkbox', 'Queixa cutânea: fotoenvelhecimento', 'queixa_fotoenvelhecimento', false),
                $f('textarea', 'Há algo mais que devemos saber?', 'observacoes_finais', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional responsável', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data_assinatura', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseToxinaBotulinica(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        $fields = [
            $f('checkbox', 'Contraindicação: gestação ou lactação', 'ctr_gestacao', false),
            $f('checkbox', 'Contraindicação: distúrbios neuromusculares', 'ctr_neuromuscular', false),
            $f('checkbox', 'Contraindicação: alergia à toxina ou albumina', 'ctr_alergia', false),
            $f('checkbox', 'Contraindicação: infecção ativa no local', 'ctr_infeccao', false),
            $f('checkbox', 'Contraindicação: aminoglicosídeos em uso', 'ctr_amino', false),
            $f('checkbox', 'Contraindicação: anticoagulantes sem liberação', 'ctr_anticoag', false),
            $f('checkbox', 'Contraindicação: distúrbios graves de coagulação', 'ctr_coagulacao', false),
            $f('checkbox', 'Contraindicação: ptose palpebral pré-existente', 'ctr_ptose', false),
            $f('radio', 'Já fez aplicação de toxina anteriormente?', 'toxina_previa', false, ['Sim', 'Não']),
            $f('date', 'Data da última aplicação', 'data_ultima_toxina', false),
            $f('select', 'Produto utilizado anteriormente', 'produto_anterior', false, [
                'Botox (Allergan)', 'Dysport (Ipsen)', 'Xeomin (Merz)', 'Prosigne', 'Outro', 'Não lembro',
            ]),
            $f('radio', 'Resultado obtido anteriormente', 'resultado_anterior', false, ['Ótimo', 'Bom', 'Regular', 'Ruim']),
            $f('select', 'Duração aproximada do efeito anterior', 'duracao_efeito', false, [
                'Menos de 2 meses', '2 a 3 meses', '3 a 4 meses', '4 a 6 meses', 'Mais de 6 meses',
            ]),
            $f('text', 'Complicações ou efeitos indesejados anteriores', 'complicacoes_previas', false),
        ];
        $zonas = [
            'testa_horiz' => 'Linhas horizontais da testa',
            'glabela' => 'Glabela (linhas do 11)',
            'pe_galinha' => 'Pés de galinha (periorbital)',
            'sobrancelha' => 'Sobrancelha (lifting)',
            'bunny' => 'Bunny lines (nariz)',
            'labio' => 'Lábio (lip flip / rugas periorais)',
            'masseter' => 'Masseter (bruxismo / slimming)',
            'platisma' => 'Pescoço (bandas de platisma)',
            'hiperhidrose' => 'Hiperhidrose (axilas / palmas / plantas)',
        ];
        foreach ($zonas as $key => $label) {
            $fields[] = $f('checkbox', "{$label} — tratar", "zona_{$key}_tratar", false);
            $fields[] = $f('checkbox', "{$label} — já tratado", "zona_{$key}_ja_tratado", false);
        }
        $fields = array_merge($fields, [
            $f('radio', 'Preferência de resultado', 'pref_resultado', false, ['Natural / suave', 'Moderado', 'Bem marcado']),
            $f('radio', 'Bruxismo diagnosticado?', 'bruxismo', false, ['Sim', 'Não', 'Suspeita']),
            $f('radio', 'Usa placa de bruxismo?', 'placa_bruxismo', false, ['Sim', 'Não']),
            $f('textarea', 'Preocupações ou dúvidas do paciente', 'duvidas', false),
            $f('select', 'Produto a utilizar (profissional)', 'produto_utilizado', false, [
                'Botox 100U (Allergan)', 'Dysport 300U (Ipsen)', 'Xeomin 100U (Merz)', 'Prosigne 100U', 'Outro',
            ]),
            $f('text', 'Lote', 'lote', false),
            $f('date', 'Validade do produto', 'validade_produto', false),
            $f('textarea', 'Unidades aplicadas por área', 'unidades_por_area', false),
            $f('textarea', 'Observações clínicas', 'obs_clinicas', false),
            $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
            $f('signature', 'Profissional responsável / registro', 'assinatura_profissional', false),
            $f('signature', 'Data', 'data', false),
        ]);

        return [
            'name' => 'Anamnese — Toxina Botulínica',
            'description' => 'Triagem, histórico, zonas de aplicação, expectativas e registro profissional.',
            'category' => 'anamneses',
            'fields' => $fields,
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseAcidoHialuronico(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Anamnese — Preenchimento com Ácido Hialurônico',
            'description' => 'Contraindicações, histórico, áreas desejadas, expectativas e registro do profissional.',
            'category' => 'anamneses',
            'fields' => [
                $f('checkbox', 'Contraindicação: gestação ou lactação', 'ctr_gestacao', false),
                $f('checkbox', 'Contraindicação: alergia ao AH ou lidocaína', 'ctr_alergia', false),
                $f('checkbox', 'Contraindicação: infecção ativa no local', 'ctr_infeccao', false),
                $f('checkbox', 'Contraindicação: doenças autoimunes ativas', 'ctr_autoimune', false),
                $f('checkbox', 'Contraindicação: coagulopatias / anticoagulantes', 'ctr_coag', false),
                $f('checkbox', 'Contraindicação: anafilaxia prévia', 'ctr_anafilaxia', false),
                $f('checkbox', 'Contraindicação: queloides', 'ctr_queloide', false),
                $f('checkbox', 'Contraindicação: isotretinoína nos últimos 6 meses', 'ctr_roacutan', false),
                $f('radio', 'Realizou preenchimento anteriormente?', 'preench_previo', false, ['Sim', 'Não']),
                $f('date', 'Data da última aplicação', 'data_ultima', false),
                $f('text', 'Produto utilizado anteriormente', 'produto_anterior', false),
                $f('radio', 'Houve complicação em aplicação anterior?', 'comp_prev', false, ['Não', 'Sim']),
                $f('text', 'Descrição da complicação anterior', 'comp_prev_desc', false),
                $f('radio', 'Já usou PMMA, silicone líquido ou permanentes?', 'permanente', false, ['Não', 'Sim']),
                $f('text', 'Onde e quando (permanentes)', 'permanente_detalhe', false),
                $f('radio', 'Toxina botulínica recente?', 'toxina_recente', false, ['Não', 'Sim']),
                $f('date', 'Data da última toxina', 'data_ultima_toxina', false),
                $f('checkbox', 'Área desejada: lábios', 'area_labios', false),
                $f('checkbox', 'Área desejada: sulco nasogeniano', 'area_sulco', false),
                $f('checkbox', 'Área desejada: malar / maçã do rosto', 'area_malar', false),
                $f('checkbox', 'Área desejada: olheiras / infraorbital', 'area_olheiras', false),
                $f('checkbox', 'Área desejada: mandíbula / jawline', 'area_mandibula', false),
                $f('checkbox', 'Área desejada: mento / queixo', 'area_mento', false),
                $f('checkbox', 'Área desejada: nariz (rinoplastia não cirúrgica)', 'area_nariz', false),
                $f('checkbox', 'Área desejada: testa / têmpora', 'area_testa', false),
                $f('checkbox', 'Área desejada: comissura labial', 'area_comissura', false),
                $f('checkbox', 'Área desejada: rugas de marionete', 'area_marionete', false),
                $f('checkbox', 'Área desejada: pescoço / colo', 'area_pescoco', false),
                $f('checkbox', 'Área desejada: mãos', 'area_maos', false),
                $f('text', 'Outras áreas / especificações', 'areas_outras', false),
                $f('radio', 'Preferência de resultado', 'pref_resultado', false, ['Natural / discreto', 'Moderado', 'Mais expressivo']),
                $f('textarea', 'O que mais incomoda na área a tratar', 'incomodo', false),
                $f('radio', 'Tem referência ou foto de resultado desejado?', 'tem_referencia', false, ['Sim', 'Não']),
                $f('radio', 'Já conhece o produto a ser utilizado?', 'conhece_produto', false, ['Sim', 'Não']),
                $f('text', 'Produto / marca utilizado (profissional)', 'produto_usado', false),
                $f('select', 'Viscosidade / tipo de gel', 'viscosidade', false, [
                    'Monofásico baixa viscosidade', 'Monofásico alta viscosidade', 'Bifásico', 'Skinbooster',
                ]),
                $f('text', 'Volume total utilizado (ml)', 'volume_total_ml', false),
                $f('text', 'Lote', 'lote', false),
                $f('date', 'Validade', 'validade', false),
                $f('select', 'Técnica utilizada', 'tecnica', false, ['Agulha', 'Cânula', 'Mista (agulha + cânula)']),
                $f('textarea', 'Volume por área e observações técnicas', 'volume_por_area', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseLaserPeeling(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Anamnese — Laser, Peeling e Procedimentos Lumínicos',
            'description' => 'Tipo de procedimento, contraindicações, Fitzpatrick, histórico solar, área e parâmetros técnicos.',
            'category' => 'anamneses',
            'fields' => [
                $f('checkbox', 'Procedimento: laser fracionado ablativo (CO₂ / Er:YAG)', 'proc_ablativo', false),
                $f('checkbox', 'Procedimento: laser fracionado não ablativo', 'proc_nao_ablativo', false),
                $f('checkbox', 'Procedimento: laser Q-Switched (manchas)', 'proc_qswitch', false),
                $f('checkbox', 'Procedimento: laser vascular (Nd:YAG, PDL)', 'proc_vascular', false),
                $f('checkbox', 'Procedimento: IPL / fotorejuvenescimento', 'proc_ipl', false),
                $f('checkbox', 'Procedimento: depilação a laser', 'proc_depilacao', false),
                $f('checkbox', 'Procedimento: peeling superficial (AHA/BHA)', 'proc_peel_sup', false),
                $f('checkbox', 'Procedimento: peeling médio (TCA)', 'proc_peel_medio', false),
                $f('checkbox', 'Procedimento: peeling profundo (fenol)', 'proc_peel_prof', false),
                $f('checkbox', 'Procedimento: microagulhamento', 'proc_micro', false),
                $f('checkbox', 'Procedimento: radiofrequência', 'proc_rf', false),
                $f('checkbox', 'Procedimento: HIFU', 'proc_hifu', false),
                $f('checkbox', 'Contraindicação: gestação ou lactação', 'ctr_gestacao', false),
                $f('checkbox', 'Contraindicação: isotretinoína (verificar suspensão)', 'ctr_isotret', false),
                $f('checkbox', 'Contraindicação: fotossensibilizantes', 'ctr_foto', false),
                $f('checkbox', 'Contraindicação: retinóides tópicos (7 dias)', 'ctr_retinoide', false),
                $f('checkbox', 'Contraindicação: exposição solar intensa recente', 'ctr_sol', false),
                $f('checkbox', 'Contraindicação: bronzeamento', 'ctr_bronze', false),
                $f('checkbox', 'Contraindicação: herpes recorrente', 'ctr_herpes', false),
                $f('checkbox', 'Contraindicação: queloides / hipertrofia', 'ctr_queloide', false),
                $f('checkbox', 'Contraindicação: vitiligo na área', 'ctr_vitiligo', false),
                $f('checkbox', 'Contraindicação: psoríase/eczema ativo', 'ctr_derm', false),
                $f('checkbox', 'Contraindicação: doença autoimune ativa', 'ctr_auto', false),
                $f('checkbox', 'Contraindicação: imunossupressão', 'ctr_imuno', false),
                $f('checkbox', 'Contraindicação: marcapasso / implantes metálicos na área', 'ctr_marcapasso', false),
                $f('checkbox', 'Contraindicação: preenchimentos na área', 'ctr_preench', false),
                $f('checkbox', 'Contraindicação: epilepsia (luz pulsada)', 'ctr_epilepsia', false),
                $f('radio', 'Fototipo de Fitzpatrick', 'fitzpatrick', false, ['I', 'II', 'III', 'IV', 'V', 'VI']),
                $f('text', 'Observações sobre fototipo / cor de pele', 'fitz_obs', false),
                $f('select', 'Última exposição solar prolongada', 'ultima_exposicao_sol', false, [
                    'Hoje', 'Últimos 7 dias', 'Últimos 15 dias', 'Últimos 30 dias', 'Há mais de 30 dias',
                ]),
                $f('radio', 'Usa protetor solar diariamente?', 'fps_diario', false, ['Sim', 'Não', 'Às vezes']),
                $f('radio', 'Usa retinóide / vitamina A tópica?', 'usa_retinoide', false, ['Sim', 'Não']),
                $f('radio', 'Usa ácidos / esfoliantes tópicos?', 'usa_acidos', false, ['Sim', 'Não']),
                $f('radio', 'Usou autobronzeador recentemente?', 'autobronze', false, ['Sim', 'Não']),
                $f('text', 'Procedimentos na área nos últimos 3 meses', 'proc_recentes', false),
                $f('checkbox', 'Área: rosto completo', 'area_rosto', false),
                $f('checkbox', 'Área: testa', 'area_testa', false),
                $f('checkbox', 'Área: periorbital', 'area_periorbital', false),
                $f('checkbox', 'Área: nariz', 'area_nariz', false),
                $f('checkbox', 'Área: bochechas', 'area_bochechas', false),
                $f('checkbox', 'Área: lábios / perioral', 'area_labios', false),
                $f('checkbox', 'Área: queixo / mandíbula', 'area_queixo', false),
                $f('checkbox', 'Área: pescoço', 'area_pescoco', false),
                $f('checkbox', 'Área: colo / décolleté', 'area_colo', false),
                $f('checkbox', 'Área: mãos', 'area_maos', false),
                $f('checkbox', 'Área: corpo (especificar)', 'area_corpo', false),
                $f('checkbox', 'Área: couro cabeludo', 'area_cc', false),
                $f('text', 'Especificações adicionais da área', 'area_obs', false),
                $f('text', 'Equipamento utilizado', 'equipamento', false),
                $f('text', 'Comprimento de onda', 'comprimento_onda', false),
                $f('text', 'Fluência (J/cm²)', 'fluencia', false),
                $f('text', 'Spot size', 'spot_size', false),
                $f('text', 'Frequência (Hz)', 'frequencia_hz', false),
                $f('text', 'Passadas realizadas', 'passadas', false),
                $f('textarea', 'Observações técnicas / intercorrências', 'obs_tecnicas', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseDepilacaoLaser(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Anamnese — Depilação a Laser',
            'description' => 'Contraindicações, perfil capilar, Fitzpatrick, áreas corporais e hormônios.',
            'category' => 'anamneses',
            'fields' => [
                $f('checkbox', 'Contraindicação: gestação ou lactação', 'ctr_gestacao', false),
                $f('checkbox', 'Contraindicação: exposição solar recente', 'ctr_sol', false),
                $f('checkbox', 'Contraindicação: bronzeamento artificial recente', 'ctr_bronze', false),
                $f('checkbox', 'Contraindicação: isotretinoína', 'ctr_iso', false),
                $f('checkbox', 'Contraindicação: fotossensibilizantes', 'ctr_foto', false),
                $f('checkbox', 'Contraindicação: epilepsia', 'ctr_epilepsia', false),
                $f('checkbox', 'Contraindicação: marcapasso / implantes eletrônicos', 'ctr_marcapasso', false),
                $f('checkbox', 'Contraindicação: tatuagens na área', 'ctr_tatuagem', false),
                $f('checkbox', 'Contraindicação: vitiligo na área', 'ctr_vitiligo', false),
                $f('checkbox', 'Contraindicação: queloides', 'ctr_queloide', false),
                $f('checkbox', 'Contraindicação: herpes ativo', 'ctr_herpes', false),
                $f('checkbox', 'Contraindicação: diabetes descompensada', 'ctr_diabetes', false),
                $f('select', 'Cor do pelo', 'cor_pelo', false, [
                    'Preto', 'Castanho escuro', 'Castanho claro', 'Loiro escuro', 'Loiro claro', 'Ruivo', 'Grisalho / branco',
                ]),
                $f('radio', 'Espessura do pelo', 'espessura', false, ['Fino', 'Médio', 'Grosso']),
                $f('radio', 'Quantidade de pelos', 'qtd_pelos', false, ['Escassa', 'Normal', 'Densa']),
                $f('radio', 'Pelos encravados frequentes?', 'encravados', false, ['Sim', 'Não', 'Às vezes']),
                $f('select', 'Método de depilação atual', 'metodo_atual', false, [
                    'Cera', 'Gilete / barbeador', 'Creme depilatório', 'Pinça', 'Linha', 'Já faz laser', 'Outro',
                ]),
                $f('select', 'Tempo desde a última depilação na área', 'tempo_ultima_dep', false, [
                    'Menos de 1 semana', '1 a 2 semanas', '2 a 4 semanas', 'Mais de 1 mês',
                ]),
                $f('radio', 'Já realizou depilação a laser anteriormente?', 'laser_antes', false, ['Sim', 'Não']),
                $f('radio', 'Fototipo de Fitzpatrick', 'fitzpatrick', false, ['I', 'II', 'III', 'IV', 'V', 'VI']),
                $f('checkbox', 'Área rosto: buço', 'ar_buco', false),
                $f('checkbox', 'Área rosto: queixo', 'ar_queixo', false),
                $f('checkbox', 'Área rosto: costeleta', 'ar_costeleta', false),
                $f('checkbox', 'Área rosto: buço + queixo', 'ar_buco_queixo', false),
                $f('checkbox', 'Área rosto: rosto completo', 'ar_rosto_compl', false),
                $f('checkbox', 'Área superior: axilas', 'ar_axilas', false),
                $f('checkbox', 'Área superior: braços completos', 'ar_bracos', false),
                $f('checkbox', 'Área superior: antebraços', 'ar_anteb', false),
                $f('checkbox', 'Área superior: mãos / dedos', 'ar_maos', false),
                $f('checkbox', 'Área superior: tórax / peito', 'ar_torax', false),
                $f('checkbox', 'Área superior: abdômen', 'ar_abd', false),
                $f('checkbox', 'Área superior: costas completas', 'ar_costas', false),
                $f('checkbox', 'Área superior: ombros', 'ar_ombros', false),
                $f('checkbox', 'Área superior: nuca / pescoço', 'ar_nuca', false),
                $f('checkbox', 'Área inferior: virilha simples', 'ar_vir_simples', false),
                $f('checkbox', 'Área inferior: virilha completa (cavado)', 'ar_vir_compl', false),
                $f('checkbox', 'Área inferior: região íntima completa', 'ar_intima', false),
                $f('checkbox', 'Área inferior: glúteos', 'ar_gluteos', false),
                $f('checkbox', 'Área inferior: coxas completas', 'ar_coxas', false),
                $f('checkbox', 'Área inferior: face interna coxas', 'ar_face_coxa', false),
                $f('checkbox', 'Área inferior: pernas completas', 'ar_pernas', false),
                $f('checkbox', 'Área inferior: panturrilhas', 'ar_pantu', false),
                $f('checkbox', 'Área inferior: pés / dedos', 'ar_pes', false),
                $f('text', 'Observações sobre as áreas (cicatrizes, tatuagens, manchas)', 'areas_obs', false),
                $f('radio', 'Distúrbio hormonal?', 'hormonal', false, ['Não', 'Sim']),
                $f('text', 'Qual distúrbio hormonal', 'hormonal_qual', false),
                $f('radio', 'Usa anticoncepcional ou reposição hormonal?', 'uso_hormonio', false, ['Sim', 'Não']),
                $f('text', 'Qual e há quanto tempo', 'hormonio_detalhe', false),
                $f('text', 'Equipamento / comprimento de onda', 'equip_onda', false),
                $f('text', 'Fluência (J/cm²)', 'fluencia', false),
                $f('text', 'Spot size / duração pulso', 'spot_pulso', false),
                $f('textarea', 'Observações técnicas', 'obs_tecnicas', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function anamneseTratamentosCorporais(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Anamnese — Tratamentos Corporais',
            'description' => 'Procedimento proposto, contraindicações, queixa, medidas, celulite e hábitos.',
            'category' => 'anamneses',
            'fields' => [
                $f('checkbox', 'Procedimento: drenagem linfática manual', 'proc_drenagem', false),
                $f('checkbox', 'Procedimento: massagem modeladora', 'proc_modeladora', false),
                $f('checkbox', 'Procedimento: criolipólise', 'proc_crio', false),
                $f('checkbox', 'Procedimento: HIFU corporal', 'proc_hifu', false),
                $f('checkbox', 'Procedimento: ultrassom cavitação', 'proc_cav', false),
                $f('checkbox', 'Procedimento: radiofrequência corporal', 'proc_rf', false),
                $f('checkbox', 'Procedimento: endermologia / LPG', 'proc_lpg', false),
                $f('checkbox', 'Procedimento: carboxiterapia', 'proc_carbox', false),
                $f('checkbox', 'Procedimento: mesoterapia corporal', 'proc_meso', false),
                $f('checkbox', 'Procedimento: eletrolipoforese', 'proc_eletro', false),
                $f('checkbox', 'Procedimento: pressoterapia', 'proc_press', false),
                $f('checkbox', 'Procedimento: lipocavitação', 'proc_lipocav', false),
                $f('checkbox', 'Contraindicação: gestação ou lactação', 'ctr_gestacao', false),
                $f('checkbox', 'Contraindicação: marcapasso / dispositivos eletrônicos', 'ctr_marcapasso', false),
                $f('checkbox', 'Contraindicação: trombose / TVP', 'ctr_trombose', false),
                $f('checkbox', 'Contraindicação: varizes / insuficiência venosa grave', 'ctr_varizes', false),
                $f('checkbox', 'Contraindicação: câncer ativo', 'ctr_cancer', false),
                $f('checkbox', 'Contraindicação: infecção ou inflamação ativa', 'ctr_infeccao', false),
                $f('checkbox', 'Contraindicação: feridas / cirurgia recente na área', 'ctr_ferida', false),
                $f('checkbox', 'Contraindicação: crioglobulinemia', 'ctr_crioglob', false),
                $f('checkbox', 'Contraindicação: doença de Raynaud', 'ctr_raynaud', false),
                $f('checkbox', 'Contraindicação: próteses metálicas na área', 'ctr_protese', false),
                $f('checkbox', 'Contraindicação: distúrbios de coagulação', 'ctr_coag', false),
                $f('checkbox', 'Contraindicação: diabetes descompensada', 'ctr_diabetes', false),
                $f('checkbox', 'Contraindicação: doenças renais ou hepáticas graves', 'ctr_renal_hep', false),
                $f('checkbox', 'Contraindicação: hipersensibilidade ao frio', 'ctr_frio', false),
                $f('checkbox', 'Queixa: celulite', 'queixa_celulite', false),
                $f('checkbox', 'Queixa: gordura localizada', 'queixa_gordura', false),
                $f('checkbox', 'Queixa: flacidez corporal', 'queixa_flacidez', false),
                $f('checkbox', 'Queixa: estrias', 'queixa_estrias', false),
                $f('checkbox', 'Queixa: retenção de líquidos', 'queixa_retencao', false),
                $f('checkbox', 'Queixa: pós-operatório', 'queixa_pos_op', false),
                $f('checkbox', 'Queixa: redução de medidas', 'queixa_medidas', false),
                $f('checkbox', 'Queixa: modelagem corporal', 'queixa_modelagem', false),
                $f('radio', 'Já realizou tratamentos corporais estéticos?', 'trat_prev', false, ['Sim', 'Não']),
                $f('text', 'Se sim, quais?', 'trat_prev_quais', false),
                $f('radio', 'Realizou cirurgia plástica?', 'cirurgia', false, ['Sim', 'Não']),
                $f('text', 'Qual cirurgia e quando', 'cirurgia_detalhe', false),
                $f('number', 'Peso (kg)', 'peso_kg', false),
                $f('number', 'Altura (cm)', 'altura_cm', false),
                $f('text', 'IMC', 'imc', false),
                $f('text', '% gordura', 'pct_gordura', false),
                $f('number', 'Cintura (cm)', 'med_cintura', false),
                $f('number', 'Quadril (cm)', 'med_quadril', false),
                $f('number', 'Abdômen (cm)', 'med_abdomen', false),
                $f('number', 'Coxa direita (cm)', 'med_coxa_d', false),
                $f('number', 'Coxa esquerda (cm)', 'med_coxa_e', false),
                $f('number', 'Braço direito (cm)', 'med_braco_d', false),
                $f('number', 'Braço esquerdo (cm)', 'med_braco_e', false),
                $f('number', 'Glúteos (cm)', 'med_gluteos', false),
                $f('select', 'Grau de celulite (Nürnberger-Müller)', 'grau_celulite', false, ['0', 'I', 'II', 'III', 'IV']),
                $f('text', 'Áreas com celulite (descrever)', 'celulite_areas', false),
                $f('select', 'Atividade física', 'atividade', false, ['Sedentário', 'Leve (1-2x/sem)', 'Moderado (3-4x/sem)', 'Intenso (5+/sem)']),
                $f('select', 'Ingestão hídrica (L/dia)', 'agua', false, ['Menos de 1L', '1 a 1,5L', '1,5 a 2L', 'Mais de 2L']),
                $f('select', 'Alimentação', 'alimentacao', false, ['Muito saudável', 'Razoavelmente saudável', 'Com muitos excessos']),
                $f('radio', 'Tabagismo', 'tabagismo', false, ['Não', 'Sim', 'Ex-fumante']),
                $f('radio', 'Em dieta / acompanhamento nutricional?', 'nutri', false, ['Sim', 'Não']),
                $f('textarea', 'Avaliação clínica inicial / protocolo proposto', 'avaliacao_protocolo', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function evolucaoSessao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Evolução de Sessão e Acompanhamento',
            'description' => 'Identificação da sessão, pré-sessão, execução, fotos, avaliação e orientações.',
            'category' => 'acompanhamento_controle',
            'fields' => [
                $f('text', 'Sessão nº', 'sessao_numero', false),
                $f('date', 'Data da sessão', 'data_sessao', false),
                $f('text', 'Tratamento / protocolo', 'tratamento_protocolo', false),
                $f('text', 'Profissional que realizou', 'profissional', false),
                $f('date', 'Próxima sessão agendada', 'proxima_sessao', false),
                $f('textarea', 'Período entre sessões — relato do paciente', 'relato_periodo', false),
                $f('radio', 'Seguiu orientações pós-sessão anterior?', 'seguiu_orient', false, [
                    'Sim, completamente', 'Parcialmente', 'Não conseguiu', 'Primeira sessão',
                ]),
                $f('radio', 'Exposição solar desde a última sessão?', 'exposicao_solar', false, ['Não', 'Sim, com proteção', 'Sim, sem proteção']),
                $f('checkbox', 'Reação pós-sessão anterior: nenhuma', 'rea_nenhuma', false),
                $f('checkbox', 'Reação: eritema leve', 'rea_eritema_leve', false),
                $f('checkbox', 'Reação: eritema intenso', 'rea_eritema_int', false),
                $f('checkbox', 'Reação: descamação', 'rea_descamacao', false),
                $f('checkbox', 'Reação: edema', 'rea_edema', false),
                $f('checkbox', 'Reação: hematomas', 'rea_hematoma', false),
                $f('checkbox', 'Reação: bolhas', 'rea_bolhas', false),
                $f('checkbox', 'Reação: hiperpigmentação', 'rea_hiper', false),
                $f('checkbox', 'Reação: hipopigmentação', 'rea_hipo', false),
                $f('checkbox', 'Reação: ardência', 'rea_ardencia', false),
                $f('checkbox', 'Reação: prurido', 'rea_prurido', false),
                $f('checkbox', 'Reação: herpes', 'rea_herpes', false),
                $f('text', 'Procedimento realizado nesta sessão', 'procedimento_realizado', false),
                $f('textarea', 'Produtos / insumos utilizados', 'produtos_insumos', false),
                $f('text', 'Parâmetros do equipamento', 'parametros_equipamento', false),
                $f('radio', 'Intercorrências durante a sessão', 'intercorrencia', false, [
                    'Nenhuma', 'Desconforto leve', 'Desconforto intenso', 'Intercorrência (detalhar)',
                ]),
                $f('textarea', 'Detalhamento de intercorrências / observações clínicas', 'intercorrencia_detalhe', false),
                $f('textarea', 'Registro fotográfico — referências (antes frente/perfil/depois)', 'fotos_referencia', false),
                $f('text', 'Fotos arquivadas em (código / pasta)', 'fotos_arquivo', false),
                $f('number', 'Avaliação: resposta ao tratamento (1–5)', 'nota_resposta', false),
                $f('number', 'Avaliação: tolerância do paciente (1–5)', 'nota_tolerancia', false),
                $f('radio', 'Manter protocolo atual?', 'manter_protocolo', false, [
                    'Sim, manter', 'Ajustar parâmetros', 'Trocar protocolo', 'Encaminhar',
                ]),
                $f('text', 'Ajuste proposto / justificativa', 'ajuste_proposto', false),
                $f('number', 'Satisfação do paciente até o momento (1–10)', 'satisfacao_paciente', false),
                $f('textarea', 'Comentários do paciente', 'comentarios_paciente', false),
                $f('textarea', 'Orientações pós-sessão prescritas', 'orientacoes_pos', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function fichaRetornoPosTratamento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Retorno e Avaliação Pós-Tratamento',
            'description' => 'Identificação do retorno, reações, resultados, NPS e próximos passos.',
            'category' => 'acompanhamento_controle',
            'fields' => [
                $f('text', 'Tratamento realizado', 'tratamento', false),
                $f('text', 'Total de sessões realizadas', 'total_sessoes', false),
                $f('date', 'Data da última sessão', 'data_ultima_sessao', false),
                $f('text', 'Profissional responsável', 'profissional', false),
                $f('select', 'Tipo de retorno', 'tipo_retorno', false, [
                    'Retorno de rotina (entre sessões)', 'Avaliação de resultado final', 'Intercorrência / complicação', 'Manutenção', 'Novo tratamento',
                ]),
                $f('checkbox', 'Reação: nenhuma', 'react_nenhuma', false),
                $f('checkbox', 'Reação: eritema', 'react_eritema', false),
                $f('checkbox', 'Reação: edema', 'react_edema', false),
                $f('checkbox', 'Reação: hematomas', 'react_hematoma', false),
                $f('checkbox', 'Reação: descamação', 'react_descamacao', false),
                $f('checkbox', 'Reação: hiperpigmentação', 'react_hiper', false),
                $f('checkbox', 'Reação: hipopigmentação', 'react_hipo', false),
                $f('checkbox', 'Reação: nódulos / irregularidades', 'react_nodulos', false),
                $f('checkbox', 'Reação: infecção / inflamação', 'react_infeccao', false),
                $f('checkbox', 'Reação: herpes', 'react_herpes', false),
                $f('checkbox', 'Reação: prurido', 'react_prurido', false),
                $f('checkbox', 'Reação: queimação', 'react_queimacao', false),
                $f('checkbox', 'Reação: dor persistente', 'react_dor', false),
                $f('textarea', 'Descrição das reações (intensidade, localização, evolução)', 'react_descricao', false),
                $f('radio', 'Atitude para tratar a reação', 'atitude_reacao', false, [
                    'Não foi necessário', 'Sim, hidratante/cicatrizante', 'Consultou outro profissional',
                ]),
                $f('radio', 'As reações já estão resolvidas?', 'reacoes_resolvidas', false, ['Sim, totalmente', 'Parcialmente', 'Ainda persistem']),
                $f('radio', 'Avaliação do profissional — resultado', 'resultado_prof', false, ['Excelente', 'Bom', 'Regular', 'Insatisfatório']),
                $f('radio', 'Avaliação do paciente — resultado', 'resultado_pac', false, ['Excelente', 'Bom', 'Regular', 'Insatisfatório']),
                $f('textarea', 'Comentários do paciente sobre o resultado', 'comentarios_paciente', false),
                $f('textarea', 'Observações clínicas do profissional', 'obs_profissional', false),
                $f('number', 'NPS — probabilidade de indicar a clínica (0 a 10)', 'nps', false),
                $f('textarea', 'O que poderia ter sido melhor?', 'nps_melhorar', false),
                $f('radio', 'Conduta após retorno', 'conduta', false, [
                    'Alta — resultado satisfatório', 'Manutenção agendada', 'Continuar protocolo', 'Ajustar protocolo', 'Tratar intercorrência', 'Encaminhar a especialista',
                ]),
                $f('date', 'Próxima consulta agendada', 'proxima_consulta', false),
                $f('text', 'Novo procedimento indicado', 'novo_procedimento', false),
                $f('signature', 'Assinatura do paciente', 'assinatura_paciente', false),
                $f('signature', 'Profissional / registro', 'assinatura_profissional', false),
                $f('signature', 'Data', 'data', false),
            ],
        ];
    }
}
