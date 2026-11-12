<?php

declare(strict_types=1);

namespace Database\Seeders\Definitions;

/**
 * Pacote documental da Dra. Stefanie Martinez Lino (odontologia estética).
 * Campos digitais para preenchimento + PDF original anexado pelo seeder.
 *
 * @return list<array{
 *   name: string,
 *   description: string,
 *   category: string,
 *   document_kind: string,
 *   source_pdf: string,
 *   fields: list<array<string, mixed>>
 * }>
 */
final class StefanieLinoDocumentPack
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function documents(): array
    {
        return [
            [
                'name' => 'Ficha de Anamnese',
                'description' => 'Anamnese clínica odontológica / estética — Dra. Stefanie Lino.',
                'category' => 'anamnese',
                'document_kind' => 'ficha',
                'source_pdf' => 'ficha-anamnese.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field('date', 'Data', 'data_anamnese', 20),
                    self::field('radio', 'É gestante ou lactante?', 'gestante_lactante', 21, true, ['Sim', 'Não']),
                    self::field('radio', 'Método anticoncepcional?', 'anticoncepcional', 22, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is) método(s)?', 'anticoncepcional_quais', 23, false),
                    self::field('radio', 'Já fez algum tipo de tratamento estético?', 'tratamento_estetico', 24, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is) tratamento(s)?', 'tratamento_estetico_quais', 25, false),
                    self::field('radio', 'Faz uso de medicamento momentâneo e/ou contínuo?', 'medicamentos', 26, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is) medicamento(s)?', 'medicamentos_quais', 27, false),
                    self::field('radio', 'Possui alergia a medicamento e/ou anestesia?', 'alergia', 28, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is) alergia(s)?', 'alergia_quais', 29, false),
                    self::field('radio', 'Já fez procedimento de harmonização facial?', 'harmonizacao_facial', 30, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is)?', 'harmonizacao_quais', 31, false),
                    self::field('radio', 'Está sob tratamento médico?', 'tratamento_medico', 32, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is)?', 'tratamento_medico_quais', 33, false),
                    self::field('radio', 'Possui bruxismo?', 'bruxismo', 34, true, ['Sim', 'Não']),
                    self::field('radio', 'Possui alteração na cor dos dentes?', 'alteracao_cor_dentes', 35, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is) alteração(ões)?', 'alteracao_cor_quais', 36, false),
                    self::field('radio', 'Tem formação sólida na pele (nódulos, pápula, sequela etc.)?', 'formacao_pele', 37, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is)?', 'formacao_pele_quais', 38, false),
                    self::field('radio', 'Faz uso de reposição hormonal?', 'reposicao_hormonal', 39, true, ['Sim', 'Não']),
                    self::field('text', 'Qual(is)?', 'reposicao_hormonal_quais', 40, false),
                    self::field('textarea', 'Observações', 'observacoes', 41, false),
                    self::field('signature', 'Assinatura do paciente', 'assinatura_paciente', 42),
                ],
            ],
            [
                'name' => 'Termo de Consentimento — Dados Pessoais (LGPD)',
                'description' => 'Consentimento para tratamento de dados pessoais — controladora Dra. Stefanie Lino.',
                'category' => 'cadastro_documentacao',
                'document_kind' => 'ciencia_lgpd',
                'source_pdf' => 'termo-dados-pessoais-lgpd.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field(
                        'notice',
                        'O texto completo das cláusulas LGPD está no PDF original anexado a este modelo. Ao assinar, o titular declara ciência e autoriza o tratamento dos dados pessoais e sensíveis para as finalidades clínicas e legais informadas.',
                        'notice_lgpd',
                        20,
                        false
                    ),
                    self::field('checkbox', 'Autorizo o tratamento dos meus dados pessoais e sensíveis conforme o termo', 'autorizo_lgpd', 21),
                    self::field('checkbox', 'Declaro que li o documento original e tive oportunidade de esclarecer dúvidas', 'li_termo_lgpd', 22),
                    self::field('signature', 'Assinatura do titular', 'assinatura_paciente', 23),
                ],
            ],
            [
                'name' => 'Termo de Cessão — Uso de Imagem',
                'description' => 'Cessão de direito de uso de imagem — Dra. Stefanie Lino (documento separado do TCLE clínico).',
                'category' => 'cadastro_documentacao',
                'document_kind' => 'ficha',
                'source_pdf' => 'termo-uso-imagem.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field(
                        'notice',
                        'O texto completo da cessão de uso de imagem está no PDF original. A autorização tem caráter determinado (conforme o termo) e segue padrões éticos vigentes.',
                        'notice_imagem',
                        20,
                        false
                    ),
                    self::field('date', 'Data de início da cessão', 'data_inicio_cessao', 21, false),
                    self::field('checkbox', 'Autorizo o uso da minha imagem conforme o termo anexado', 'autorizo_imagem', 22),
                    self::field('signature', 'Assinatura do cedente', 'assinatura_paciente', 23),
                ],
            ],
            [
                'name' => 'TCLE — Facetas Diretas em Resina Composta',
                'description' => 'Termo de consentimento informado e esclarecido para facetas em resina.',
                'category' => 'consentimento',
                'document_kind' => 'consentimento',
                'source_pdf' => 'termo-consentimento-facetas-resina.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field(
                        'notice',
                        'Procedimento: facetas diretas em resina composta. Riscos, benefícios, indicações e contraindicações constam no PDF original anexado. Leia o documento completo antes de assinar.',
                        'notice_facetas',
                        20,
                        false
                    ),
                    self::field('checkbox', 'Declaro que li e compreendi as informações sobre o procedimento', 'decl_leitura', 21),
                    self::field('checkbox', 'Minhas dúvidas foram esclarecidas pelo profissional', 'decl_duvidas', 22),
                    self::field('checkbox', 'Autorizo a realização do procedimento de forma livre e esclarecida', 'decl_autorizo', 23),
                    self::field('signature', 'Assinatura do paciente', 'assinatura_paciente', 24),
                ],
            ],
            [
                'name' => 'TCLE — Remoção de Facetas',
                'description' => 'Termo de consentimento para remoção de facetas.',
                'category' => 'consentimento',
                'document_kind' => 'consentimento',
                'source_pdf' => 'termo-remocao-facetas.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field(
                        'notice',
                        'A remoção pode ser feita com brocas diamantadas e/ou laser, eventualmente complementada com ultrassom. Detalhes técnicos e riscos estão no PDF original.',
                        'notice_remocao',
                        20,
                        false
                    ),
                    self::field('checkbox', 'Declaro ciência das técnicas e riscos da remoção de facetas', 'decl_ciencia_remocao', 21),
                    self::field('checkbox', 'Autorizo a remoção das facetas conforme orientação profissional', 'decl_autorizo_remocao', 22),
                    self::field('signature', 'Assinatura do paciente', 'assinatura_paciente', 23),
                ],
            ],
            [
                'name' => 'Contrato de Prestação de Serviços da Saúde',
                'description' => 'Contrato geral de prestação de serviços de saúde — Dra. Stefanie Lino.',
                'category' => 'cadastro_documentacao',
                'document_kind' => 'ficha',
                'source_pdf' => 'contrato-servicos-saude.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field('text', 'Instagram', 'instagram', 20, false),
                    self::field('textarea', 'Tratamento(s) / procedimento(s) contratado(s)', 'tratamentos', 21),
                    self::field('text', 'Valor (R$)', 'valor', 22, false),
                    self::field('text', 'Modalidade de pagamento', 'modalidade_pagamento', 23, false),
                    self::field(
                        'notice',
                        'Cláusulas contratuais completas (objeto, preço, obrigações, cancelamentos) estão no PDF original anexado.',
                        'notice_contrato_saude',
                        24,
                        false
                    ),
                    self::field('checkbox', 'Declaro ciência e aceito as cláusulas do contrato anexado', 'aceite_contrato', 25),
                    self::field('signature', 'Assinatura do contratante', 'assinatura_paciente', 26),
                ],
            ],
            [
                'name' => 'Contrato de Prestação de Serviços Odontológicos',
                'description' => 'Contrato odontológico (facetas diretas em resina composta).',
                'category' => 'cadastro_documentacao',
                'document_kind' => 'ficha',
                'source_pdf' => 'contrato-servicos-odontologicos.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field('text', 'Instagram', 'instagram', 20, false),
                    self::field(
                        'notice',
                        'Objeto principal: Facetas Diretas em Resina Composta. Demais cláusulas (preço, obrigações, cancelamento) no PDF original.',
                        'notice_contrato_odonto',
                        21,
                        false
                    ),
                    self::field('text', 'Valor (R$)', 'valor', 22, false),
                    self::field('text', 'Modalidade de pagamento', 'modalidade_pagamento', 23, false),
                    self::field('checkbox', 'Declaro ciência e aceito as cláusulas do contrato odontológico', 'aceite_contrato_odonto', 24),
                    self::field('signature', 'Assinatura do contratante', 'assinatura_paciente', 25),
                ],
            ],
            [
                'name' => 'Contrato Particular para Tratamento Odontológico',
                'description' => 'Contrato particular com planejamento / tabela de eventos.',
                'category' => 'cadastro_documentacao',
                'document_kind' => 'ficha',
                'source_pdf' => 'contrato-particular-tratamento.pdf',
                'fields' => self::patientIdentityFields(1),
                'extra_fields' => [
                    self::field('textarea', 'Estado atual / odontograma (resumo)', 'estado_atual', 20, false),
                    self::field('textarea', 'Planejamento / tratamento a realizar', 'planejamento', 21),
                    self::field('textarea', 'Tabela de eventos / procedimentos', 'tabela_eventos', 22, false),
                    self::field(
                        'notice',
                        'O layout completo com odontograma e cláusulas está no PDF original anexado.',
                        'notice_contrato_particular',
                        23,
                        false
                    ),
                    self::field('checkbox', 'Aceito o planejamento e as condições do contrato particular', 'aceite_contrato_particular', 24),
                    self::field('signature', 'Assinatura do contratante', 'assinatura_paciente', 25),
                ],
            ],
            [
                'name' => 'Termo de Finalização e Satisfação de Resultado',
                'description' => 'Encerramento do tratamento com declaração de satisfação do paciente.',
                'category' => 'acompanhamento',
                'document_kind' => 'ficha',
                'source_pdf' => 'termo-finalizacao-satisfacao.pdf',
                'fields' => [
                    self::field('text', 'Nome do paciente', 'nome_completo', 1),
                    self::field('date', 'Data do procedimento', 'data_procedimento', 2),
                    self::field('textarea', 'Tratamento finalizado', 'tratamento_finalizado', 3),
                    self::field(
                        'notice',
                        'Ao assinar, o paciente declara satisfação com o resultado e ciência de que respostas anatômicas/fisiológicas são individuais. Texto completo no PDF original.',
                        'notice_finalizacao',
                        4,
                        false
                    ),
                    self::field('checkbox', 'Declaro satisfação com o procedimento e resultado obtidos', 'declaro_satisfacao', 5),
                    self::field('signature', 'Assinatura do paciente', 'assinatura_paciente', 6),
                ],
                'extra_fields' => [],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function patientIdentityFields(int $startOrder): array
    {
        $i = $startOrder;

        return [
            self::field('text', 'Nome completo', 'nome_completo', $i++),
            self::field('text', 'Nacionalidade', 'nacionalidade', $i++, false),
            self::field('select', 'Estado civil', 'estado_civil', $i++, false, ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União estável']),
            self::field('text', 'Profissão', 'profissao', $i++, false),
            self::field('text', 'RG', 'rg', $i++, false),
            self::field('text', 'CPF', 'cpf', $i++),
            self::field('date', 'Data de nascimento', 'data_nascimento', $i++, false),
            self::field('text', 'Telefone', 'telefone', $i++, false),
            self::field('text', 'E-mail', 'email', $i++, false),
            self::field('text', 'Endereço', 'endereco', $i++, false),
        ];
    }

    /**
     * @param  list<string>|null  $options
     * @return array<string, mixed>
     */
    private static function field(
        string $type,
        string $label,
        string $nameKey,
        int $order,
        bool $required = true,
        ?array $options = null,
    ): array {
        $f = [
            'type' => $type,
            'label' => $label,
            'name_key' => $nameKey,
            'sort_order' => $order,
            'required' => in_array($type, ['heading', 'notice', 'section_break'], true) ? false : $required,
        ];
        if ($options !== null) {
            $f['options'] = $options;
        }

        return $f;
    }
}
