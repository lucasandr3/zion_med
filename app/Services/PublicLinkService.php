<?php

namespace App\Services;

use App\Models\FormTemplate;
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

    public function getPublicUrl(FormTemplate $template): string
    {
        if (! $template->public_token) {
            return '';
        }
        return route('formulario-publico.show', ['token' => $template->public_token]);
    }
}
