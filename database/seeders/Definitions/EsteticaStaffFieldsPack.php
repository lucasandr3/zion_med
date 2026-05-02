<?php

declare(strict_types=1);

namespace Database\Seeders\Definitions;

/**
 * Campos da equipe — preenchidos no protocolo (não no formulário público /f/).
 *
 * @phpstan-type StaffFieldDef array{type: string, label: string, name_key: string, sort_order: int, required?: bool, options?: array<int, string>}
 */
final class EsteticaStaffFieldsPack
{
    /** @param array<int, string>|null $options */
    private static function field(string $type, string $label, string $nameKey, int $order, bool $required = false, ?array $options = null): array
    {
        $f = ['type' => $type, 'label' => $label, 'name_key' => $nameKey, 'sort_order' => $order, 'required' => $required];
        if ($options !== null) {
            $f['options'] = $options;
        }

        return $f;
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    public static function fieldsForTemplateName(string $name): array
    {
        return match ($name) {
            'Ficha de Cadastro do Paciente' => self::cadastroPaciente(),
            'TCLE — Termo de Consentimento Livre e Esclarecido' => self::tcleConsentimento(),
            'Autorização de Imagem e Proteção de Dados (LGPD)' => self::autorizacaoImagemLgpd(),
            'Orçamento e Plano de Tratamento' => self::orcamentoPlanoTratamento(),
            'Anamnese Geral' => self::anamneseGeral(),
            'Anamnese — Toxina Botulínica' => self::anamneseToxinaBotulinica(),
            'Anamnese — Preenchimento com Ácido Hialurônico' => self::anamneseAcidoHialuronico(),
            'Anamnese — Laser, Peeling e Procedimentos Lumínicos' => self::anamneseLaserPeeling(),
            'Anamnese — Depilação a Laser' => self::anamneseDepilacaoLaser(),
            'Anamnese — Tratamentos Corporais' => self::anamneseTratamentosCorporais(),
            'Evolução de Sessão e Acompanhamento' => self::evolucaoSessao(),
            'Retorno e Avaliação Pós-Tratamento' => self::fichaRetornoPosTratamento(),
            default => [],
        };
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function cadastroPaciente(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Número da ficha (clínica)', 'ficha_numero', false),
            $f('date', 'Data de registro na clínica', 'data', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function tcleConsentimento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Procedimento(s) a ser(em) realizado(s)', 'procedimentos', false),
            $f('text', 'Profissional responsável', 'profissional_responsavel', false),
            $f('date', 'Data do procedimento', 'data_procedimento', false),
            $f('textarea', 'Descrição específica do procedimento e técnica utilizada', 'descricao_procedimento_tecnica', false),
            $f('textarea', 'Benefícios esperados', 'beneficios_esperados', false),
            $f('textarea', 'Riscos e efeitos colaterais possíveis', 'riscos_efeitos_colaterais', false),
            $f('text', 'Número de sessões previstas', 'numero_sessoes_previstas', false),
            $f('text', 'Intervalo entre sessões', 'intervalo_sessoes', false),
            $f('text', 'Valor do procedimento / pacote', 'valor_procedimento', false),
            $f('textarea', 'Orientações pós-procedimento', 'orientacoes_pos', false),
            $f('text', 'Registro do profissional (nome e conselho)', 'assinatura_profissional', false),
            $f('text', 'Data e local (conferência clínica)', 'data_conferencia_clinica', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function autorizacaoImagemLgpd(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Encarregado de dados / responsável pela clínica (nome e cargo)', 'assinatura_encarregado', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function orcamentoPlanoTratamento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        $fields = [
            $f('text', 'Número do orçamento', 'orcamento_numero', false),
            $f('date', 'Data do orçamento', 'data_orcamento', false),
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
            $f('select', 'Frequência das sessões', 'frequencia_sessoes', false, [
                '1x por semana', '2x por semana', 'A cada 15 dias', '1x por mês', 'Outra frequência',
            ]),
            $f('text', 'Duração estimada do tratamento', 'duracao_estimada', false),
            $f('date', 'Data de início prevista', 'data_inicio_prevista', false),
            $f('textarea', 'Observações sobre o plano', 'observacoes_plano', false),
            $f('text', 'Responsável comercial / clínico (nome e registro)', 'assinatura_responsavel_comercial', false),
        ]);

        return $fields;
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseGeral(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Profissional — conferência (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseToxinaBotulinica(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('select', 'Produto a utilizar', 'produto_utilizado', false, [
                'Botox 100U (Allergan)', 'Dysport 300U (Ipsen)', 'Xeomin 100U (Merz)', 'Prosigne 100U', 'Outro',
            ]),
            $f('text', 'Lote', 'lote', false),
            $f('date', 'Validade do produto', 'validade_produto', false),
            $f('textarea', 'Unidades aplicadas por área', 'unidades_por_area', false),
            $f('textarea', 'Observações clínicas', 'obs_clinicas', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseAcidoHialuronico(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Produto / marca utilizado', 'produto_usado', false),
            $f('select', 'Viscosidade / tipo de gel', 'viscosidade', false, [
                'Monofásico baixa viscosidade', 'Monofásico alta viscosidade', 'Bifásico', 'Skinbooster',
            ]),
            $f('text', 'Volume total utilizado (ml)', 'volume_total_ml', false),
            $f('text', 'Lote', 'lote', false),
            $f('date', 'Validade', 'validade', false),
            $f('select', 'Técnica utilizada', 'tecnica', false, ['Agulha', 'Cânula', 'Mista (agulha + cânula)']),
            $f('textarea', 'Volume por área e observações técnicas', 'volume_por_area', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseLaserPeeling(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Equipamento utilizado', 'equipamento', false),
            $f('text', 'Comprimento de onda', 'comprimento_onda', false),
            $f('text', 'Fluência (J/cm²)', 'fluencia', false),
            $f('text', 'Spot size', 'spot_size', false),
            $f('text', 'Frequência (Hz)', 'frequencia_hz', false),
            $f('text', 'Passadas realizadas', 'passadas', false),
            $f('textarea', 'Observações técnicas / intercorrências', 'obs_tecnicas', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseDepilacaoLaser(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Equipamento / comprimento de onda', 'equip_onda', false),
            $f('text', 'Fluência (J/cm²)', 'fluencia', false),
            $f('text', 'Spot size / duração pulso', 'spot_pulso', false),
            $f('textarea', 'Observações técnicas', 'obs_tecnicas', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function anamneseTratamentosCorporais(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('textarea', 'Avaliação clínica inicial / protocolo proposto', 'avaliacao_protocolo', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function evolucaoSessao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Sessão nº', 'sessao_numero', false),
            $f('date', 'Data da sessão', 'data_sessao', false),
            $f('text', 'Tratamento / protocolo', 'tratamento_protocolo', false),
            $f('text', 'Profissional que realizou', 'profissional', false),
            $f('date', 'Próxima sessão agendada', 'proxima_sessao', false),
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
            $f('textarea', 'Orientações pós-sessão prescritas', 'orientacoes_pos', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }

    /**
     * @return array<int, StaffFieldDef>
     */
    private static function fichaRetornoPosTratamento(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            $f('text', 'Profissional responsável', 'profissional', false),
            $f('radio', 'Avaliação do profissional — resultado', 'resultado_prof', false, ['Excelente', 'Bom', 'Regular', 'Insatisfatório']),
            $f('textarea', 'Observações clínicas do profissional', 'obs_profissional', false),
            $f('radio', 'Atitude para tratar a reação', 'atitude_reacao', false, [
                'Não foi necessário', 'Sim, hidratante/cicatrizante', 'Consultou outro profissional',
            ]),
            $f('radio', 'As reações já estão resolvidas?', 'reacoes_resolvidas', false, ['Sim, totalmente', 'Parcialmente', 'Ainda persistem']),
            $f('radio', 'Conduta após retorno', 'conduta', false, [
                'Alta — resultado satisfatório', 'Manutenção agendada', 'Continuar protocolo', 'Ajustar protocolo', 'Tratar intercorrência', 'Encaminhar a especialista',
            ]),
            $f('date', 'Próxima consulta agendada', 'proxima_consulta', false),
            $f('text', 'Novo procedimento indicado', 'novo_procedimento', false),
            $f('text', 'Profissional — registro (nome e conselho)', 'assinatura_profissional', false),
        ];
    }
}
