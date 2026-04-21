<?php

namespace App\Support;

use App\Models\FormSubmission;

/**
 * URLs do painel Angular (SPA). O backend não expõe rotas web nomeadas para telas internas.
 */
final class FrontendUrl
{
    private static function base(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    /** Rota Angular: /protocolos/:id */
    public static function protocoloDetalhe(FormSubmission|int $submission): string
    {
        $id = $submission instanceof FormSubmission ? $submission->id : $submission;

        return self::base()."/protocolos/{$id}";
    }

    /** Rota Angular: /plataforma/leads */
    public static function plataformaLeads(): string
    {
        return self::base().'/plataforma/leads';
    }

    /** Rota Angular: /plataforma/faturas */
    public static function plataformaFaturas(): string
    {
        return self::base().'/plataforma/faturas';
    }

    /** Rota Angular: /plataforma/assinaturas */
    public static function plataformaAssinaturas(): string
    {
        return self::base().'/plataforma/assinaturas';
    }
}
