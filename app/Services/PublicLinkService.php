<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Support\FrontendUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicLinkService
{
    public function generateToken(FormTemplate $template): string
    {
        $token = Str::random(32);
        $template->update([
            'public_token' => $token,
            'public_enabled' => true,
        ]);

        return $token;
    }

    public function disablePublicLink(FormTemplate $template): void
    {
        $template->update([
            'public_token' => null,
            'public_enabled' => false,
        ]);
    }

    /**
     * URL pública do formulário.
     *
     * Com Request (painel / API): usa a origem real do SPA (Origin/?front=).
     * Sem Request (e-mail / fila): usa FRONTEND_URL canônica.
     */
    public function getPublicUrl(FormTemplate $template, ?Request $request = null): string
    {
        if (! $template->public_token) {
            return '';
        }

        if ($request instanceof Request) {
            return FrontendUrl::formularioPublico($template->public_token, $request);
        }

        return FrontendUrl::base().'/f/'.$template->public_token;
    }
}
