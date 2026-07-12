<?php

namespace App\Support;

final class ClinicalStepKind
{
    public const DADOS_PACIENTE = 'dados_paciente';

    public const DESCRICAO_PROCEDIMENTO = 'descricao_procedimento';

    public const RISCOS_BENEFICIOS = 'riscos_beneficios';

    public const ALTERNATIVAS = 'alternativas';

    public const DECLARACOES = 'declaracoes';

    public const COMPREENSAO = 'compreensao';

    public const PRIVACIDADE_LGPD = 'privacidade_lgpd';

    public const ASSINATURAS = 'assinaturas';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DADOS_PACIENTE,
            self::DESCRICAO_PROCEDIMENTO,
            self::RISCOS_BENEFICIOS,
            self::ALTERNATIVAS,
            self::DECLARACOES,
            self::COMPREENSAO,
            self::PRIVACIDADE_LGPD,
            self::ASSINATURAS,
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::DADOS_PACIENTE => 'Dados do paciente',
            self::DESCRICAO_PROCEDIMENTO => 'Descrição do procedimento',
            self::RISCOS_BENEFICIOS => 'Riscos e benefícios',
            self::ALTERNATIVAS => 'Alternativas',
            self::DECLARACOES => 'Declarações',
            self::COMPREENSAO => 'Compreensão clínica',
            self::PRIVACIDADE_LGPD => 'Privacidade (LGPD)',
            self::ASSINATURAS => 'Assinaturas',
        ];
    }

    public static function labelFor(?string $kind): string
    {
        if ($kind === null || $kind === '') {
            return 'Etapa do formulário';
        }

        return self::labels()[$kind] ?? 'Etapa do formulário';
    }

    /** Etapas recomendadas para TCLE (consentimento). */
    /** @return list<string> */
    public static function recommendedForConsent(): array
    {
        return [
            self::DADOS_PACIENTE,
            self::DESCRICAO_PROCEDIMENTO,
            self::RISCOS_BENEFICIOS,
            self::ALTERNATIVAS,
            self::DECLARACOES,
            self::COMPREENSAO,
            self::PRIVACIDADE_LGPD,
            self::ASSINATURAS,
        ];
    }
}
