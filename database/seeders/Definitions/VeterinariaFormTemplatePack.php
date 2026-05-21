<?php

declare(strict_types=1);

namespace Database\Seeders\Definitions;

/**
 * Modelos padrão do nicho Veterinária (cadastro do pet/tutor, internação, cirurgia, vacinas, etc.).
 *
 * @phpstan-type FieldDef array{type: string, label: string, name_key: string, sort_order: int, required?: bool, options?: array<int, string>}
 */
final class VeterinariaFormTemplatePack
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
            self::cadastroTutorAnimal(),
            self::termoAutorizacaoInternacao(),
            self::termoConsentimentoCirurgia(),
            self::cartaoVacinacao(),
            self::evolucaoInternacao(),
            self::altaOrientacoes(),
            self::autorizacaoImagemAnimal(),
            self::pesquisaSatisfacao(),
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function cadastroTutorAnimal(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Ficha de Cadastro do Tutor e do Animal',
            'description' => 'Dados do responsável (tutor) e identificação do animal para atendimento e internação.',
            'category' => 'cadastro_documentacao',
            'fields' => [
                $f('text', 'Nome completo do tutor / responsável', 'nome_tutor'),
                $f('text', 'CPF do tutor', 'cpf_tutor'),
                $f('text', 'RG do tutor', 'rg_tutor', false),
                $f('text', 'Telefone / WhatsApp', 'telefone_tutor'),
                $f('text', 'Telefone alternativo', 'telefone_alt', false),
                $f('text', 'E-mail', 'email_tutor', false),
                $f('text', 'Endereço completo', 'endereco_tutor'),
                $f('text', 'Bairro', 'bairro_tutor', false),
                $f('text', 'Cidade / UF', 'cidade_uf_tutor', false),
                $f('text', 'CEP', 'cep_tutor', false),
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('select', 'Espécie', 'especie', true, ['Canina', 'Felina', 'Equina', 'Bovina', 'Ave', 'Réptil', 'Outra']),
                $f('text', 'Raça', 'raca'),
                $f('radio', 'Sexo', 'sexo_animal', true, ['Macho', 'Fêmea']),
                $f('text', 'Idade (anos, meses e dias)', 'idade_animal'),
                $f('text', 'Cor / pelagem', 'cor_pelagem', false),
                $f('select', 'Status reprodutivo', 'status_reprodutivo', false, ['Fértil', 'Castrado(a)', 'Não se aplica']),
                $f('text', 'Microchip / registro', 'microchip', false),
                $f('textarea', 'Observações sobre o animal', 'obs_animal', false),
                $f('signature', 'Assinatura do tutor / responsável', 'assinatura_tutor', false),
            ],
        ];
    }

    /**
     * Termo baseado no contrato de prestação de serviços e autorização para internação (modelo clínico veterinária 24h).
     *
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function termoAutorizacaoInternacao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Termo de Autorização para Internação e Tratamento Clínico',
            'description' => 'Contrato de prestação de serviços, consentimento para internação, honorários, visitas e assinatura do tutor.',
            'category' => 'consentimento',
            'fields' => [
                $f('text', 'Nome do tutor / responsável (contratante)', 'nome_tutor'),
                $f('text', 'CPF do tutor', 'cpf_tutor'),
                $f('text', 'Endereço completo do tutor', 'endereco_tutor'),
                $f('text', 'Telefone / WhatsApp', 'telefone_tutor'),
                $f('text', 'E-mail', 'email_tutor', false),
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('select', 'Espécie', 'especie', true, ['Canina', 'Felina', 'Equina', 'Bovina', 'Ave', 'Réptil', 'Outra']),
                $f('text', 'Raça', 'raca'),
                $f('radio', 'Sexo do animal', 'sexo_animal', true, ['Macho', 'Fêmea']),
                $f('text', 'Idade do animal', 'idade_animal'),
                $f('text', 'Cor / pelagem', 'cor_pelagem', false),
                $f('select', 'Status reprodutivo', 'status_reprodutivo', false, ['Fértil', 'Castrado(a)', 'Não se aplica']),
                $f('textarea', 'Cláusula 1ª — Motivo da internação', 'motivo_internacao'),
                $f('checkbox', '2.1 — Autorizo exames, tratamentos e testes diagnósticos complementares que o(s) médico(s) veterinário(s) julgarem necessários', 'aut_exames_tratamentos', false),
                $f('checkbox', '2.2 — Autorizo sedativos e/ou anestésicos necessários; estou ciente de possíveis complicações mesmo com perícia e prudência', 'aut_sedacao_anestesia', false),
                $f('checkbox', '2.3 — Autorizo tricotomia (retirada de pelo) quando necessária para higiene e segurança do tratamento', 'aut_tricotomia', false),
                $f('checkbox', '2.4 — Em caso de óbito, comprometo-me com a destinação correta do animal conforme orientação da clínica', 'aut_destinacao_obito', false),
                $f('checkbox', '2.5 — Li e compreendi este termo para tratamentos, inclusive cirúrgicos e testes diagnósticos', 'aut_leitura_termo', false),
                $f('checkbox', '2.6 — Recebi informações necessárias do médico veterinário e concordo com os procedimentos propostos', 'aut_info_mv', false),
                $f('checkbox', '2.7 — Estou ciente dos riscos inerentes à situação clínica e ao(s) tratamento(s) proposto(s)', 'aut_riscos', false),
                $f('checkbox', '2.8 — Estou ciente de que abandonar animais é crime (Lei 9.605/98) e serei tutor presente durante o tratamento', 'aut_abandono_lei', false),
                $f('checkbox', '2.9 — Após a alta, tomarei os cuidados necessários e comunicarei imediatamente complicações ao médico veterinário', 'aut_cuidados_pos_alta', false),
                $f('text', 'Valor da primeira diária de internação 24h (R$)', 'valor_diaria_24h', false),
                $f('text', 'Valor do kit de soro (R$)', 'valor_kit_soro', false),
                $f('text', 'Valor da taxa de descartáveis (R$)', 'valor_taxa_descartaveis', false),
                $f('checkbox', '3.1 — Estou ciente de cobranças separadas a cada 24h (medicamentos, materiais, exames, procedimentos autorizados, tapetes higiênicos)', 'aut_cobrancas_24h', false),
                $f('checkbox', '3.2 — Em internação prolongada, valores serão acrescidos conforme dias, medicação e honorários, inclusive em caso de óbito', 'aut_internacao_prolongada', false),
                $f('checkbox', '3.3 — Atualizarei o pagamento diariamente; se não puder pagar, retirarei o animal sem alta médica conforme protocolo e Res. CFMV 1138/2016', 'aut_pagamento_diario', false),
                $f('text', 'Horário de visitas (ex.: 09:30 às 10:00 e 21:30 às 22:00)', 'horario_visitas', false),
                $f('text', 'Horário de informações clínicas / prontuário (ex.: 07:00–09:00 e 19:00–21:00)', 'horario_prontuario', false),
                $f('checkbox', '3.4 — Comprometo-me a visitar o animal apenas nos horários informados pela clínica', 'aut_horario_visitas', false),
                $f('checkbox', '3.5 — Se não puder visitar, ligarei no horário de prontuário para receber informações clínicas', 'aut_horario_prontuario', false),
                $f('checkbox', '4.1 — Autorizo uso gratuito e por prazo indeterminado de imagens do animal (foto/vídeo) em mídias sociais da clínica, fins educativos ou promocionais', 'aut_imagem_gratuita', false),
                $f('checkbox', '4.2 — Não autorizo uso de imagens do animal além da documentação clínica', 'aut_imagem_nao', false),
                $f('checkbox', '5 — Estou ciente do prazo de desistência de 7 dias (CDC art. 49); valores da cláusula 3 permanecem devidos em caso de rescisão', 'aut_rescisao_cdc', false),
                $f('checkbox', '6 — Declaro conhecer e concordar com todas as cláusulas deste contrato (pacta sunt servanda)', 'aut_disposicoes_gerais', false),
                $f('textarea', 'Observações gerais (proprietário / responsável)', 'observacoes_gerais', false),
                $f('text', 'Cidade', 'cidade_assinatura', false),
                $f('date', 'Data da assinatura', 'data_assinatura'),
                $f('signature', 'Assinatura do tutor / responsável pelo animal', 'assinatura_tutor'),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function termoConsentimentoCirurgia(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Termo de Consentimento para Cirurgia Veterinária',
            'description' => 'Autorização para procedimento cirúrgico, anestesia e riscos.',
            'category' => 'consentimento',
            'fields' => [
                $f('text', 'Nome do tutor / responsável', 'nome_tutor'),
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('date', 'Data', 'data'),
                $f('textarea', 'Procedimento cirúrgico previsto', 'procedimento'),
                $f('checkbox', 'Autorizo a cirurgia e anestesia; fui informado(a) sobre riscos e cuidados pós-operatórios', 'aut_cirurgia', false),
                $f('checkbox', 'Autorizo medidas de emergência durante o ato cirúrgico, se necessárias', 'aut_emergencia', false),
                $f('signature', 'Assinatura do tutor / responsável', 'assinatura_tutor', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function cartaoVacinacao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Cartão de Vacinação do Animal',
            'description' => 'Registro de vacina, dose, lote e próxima aplicação.',
            'category' => 'acompanhamento_controle',
            'fields' => [
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('text', 'Nome do tutor', 'nome_tutor', false),
                $f('text', 'Vacina / imunobiológico', 'vacina'),
                $f('text', 'Dose / lote', 'dose_lote', false),
                $f('date', 'Data da aplicação', 'data_aplicacao'),
                $f('date', 'Próxima dose / reforço', 'proxima_dose', false),
                $f('text', 'Médico veterinário responsável', 'mv_responsavel', false),
                $f('textarea', 'Observações', 'observacoes', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function evolucaoInternacao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Evolução de Internação',
            'description' => 'Registro diário de evolução clínica do animal internado.',
            'category' => 'evolucao',
            'fields' => [
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('date', 'Data', 'data'),
                $f('textarea', 'Evolução clínica / conduta', 'evolucao'),
                $f('textarea', 'Medicações e fluidoterapia', 'medicacoes', false),
                $f('textarea', 'Exames solicitados / realizados', 'exames', false),
                $f('text', 'Médico veterinário', 'mv_responsavel', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function altaOrientacoes(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Alta e Orientações Pós-internação',
            'description' => 'Orientações de alta, medicação e retorno.',
            'category' => 'acompanhamento',
            'fields' => [
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('text', 'Nome do tutor', 'nome_tutor', false),
                $f('date', 'Data da alta', 'data_alta'),
                $f('textarea', 'Diagnóstico / resumo da internação', 'resumo_internacao', false),
                $f('textarea', 'Medicação e cuidados em domicílio', 'orientacoes_medicacao'),
                $f('textarea', 'Sinais de alerta — retornar imediatamente se', 'sinais_alerta', false),
                $f('date', 'Retorno agendado', 'retorno_agendado', false),
                $f('signature', 'Assinatura do tutor (ciência das orientações)', 'assinatura_tutor', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function autorizacaoImagemAnimal(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Autorização de Uso de Imagem do Animal',
            'description' => 'Consentimento para fotos e vídeos em redes sociais e materiais da clínica.',
            'category' => 'cadastro_documentacao',
            'fields' => [
                $f('text', 'Nome do tutor', 'nome_tutor'),
                $f('text', 'Nome do animal', 'nome_animal'),
                $f('checkbox', 'Autorizo uso de imagens em redes sociais e divulgação (fins educativos ou promocionais)', 'aut_redes', false),
                $f('checkbox', 'Autorizo apenas documentação clínica (sem divulgação)', 'aut_so_clinica', false),
                $f('checkbox', 'Não autorizo qualquer uso de imagem além do prontuário', 'aut_nao', false),
                $f('signature', 'Assinatura do tutor', 'assinatura_tutor', false),
                $f('date', 'Data', 'data', false),
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, category: string, fields: array<int, FieldDef>}
     */
    private static function pesquisaSatisfacao(): array
    {
        $o = 0;
        $f = static fn (string $type, string $label, string $key, bool $req = false, ?array $opt = null) => self::field($type, $label, $key, ++$o, $req, $opt);

        return [
            'name' => 'Pesquisa de Satisfação (Tutor)',
            'description' => 'Avaliação do atendimento veterinário.',
            'category' => 'acompanhamento_controle',
            'fields' => [
                $f('date', 'Data', 'data'),
                $f('number', 'Nota geral (0 a 10)', 'nota'),
                $f('textarea', 'Comentários', 'comentarios', false),
            ],
        ];
    }
}
