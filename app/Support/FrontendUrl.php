<?php

namespace App\Support;

use App\Models\FormSubmission;
use Illuminate\Http\Request;

/**
 * URLs do painel Angular (SPA). O backend não expõe rotas web nomeadas para telas internas.
 */
final class FrontendUrl
{
    /**
     * Base canônica do SPA (`FRONTEND_URL`) — e-mails, filas, links persistidos para o paciente.
     */
    public static function base(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    /**
     * Base do SPA para respostas da API ao cliente atual.
     * Prioriza ?front=, Origin/Referer (porta real do ng serve / host do app), depois FRONTEND_URL.
     */
    public static function spaBase(?Request $request = null): string
    {
        $request ??= request();

        if ($request instanceof Request) {
            $frontQuery = trim((string) $request->query('front', ''));
            if ($frontQuery === '' && $request->has('front')) {
                $frontQuery = trim((string) $request->input('front', ''));
            }
            if ($frontQuery !== '' && preg_match('#^https?://[^\s/]+#i', $frontQuery)) {
                $resolved = self::originFromUrl($frontQuery);
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            $origin = trim((string) $request->headers->get('Origin', ''));
            if ($origin !== '' && preg_match('#^https?://#i', $origin)) {
                return rtrim($origin, '/');
            }

            $referer = trim((string) $request->headers->get('Referer', ''));
            if ($referer !== '') {
                $resolved = self::originFromUrl($referer);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return self::base();
    }

    private static function originFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
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

    public static function plataformaCliente(int $organizationId): string
    {
        return self::base().'/plataforma/clientes/'.$organizationId;
    }

    /** Rota Angular: /billing (assinatura) */
    public static function billing(): string
    {
        return self::base().'/assinatura';
    }

    /** Rota Angular: onboarding / templates */
    public static function onboarding(): string
    {
        return self::base().'/templates';
    }

    /** Formulário público: /f/:token */
    public static function formularioPublico(string $token, ?Request $request = null): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        return self::spaBase($request).'/f/'.$token;
    }
}
