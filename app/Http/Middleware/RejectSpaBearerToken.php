<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tokens emitidos para o SPA (nome "spa") não autenticam mais.
 * O app usa cookie de sessão; Bearer fica para tokens de integração.
 */
class RejectSpaBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken && $token->name === 'spa') {
            if ($token->getKey() !== null) {
                $token->delete();
            }

            throw new AuthenticationException('Sessão do app expirada. Entre novamente.');
        }

        return $next($request);
    }
}
