<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiErrorResponse
{
    public static function make(
        string $code,
        string $message,
        int $status = 400,
        mixed $details = null,
    ): JsonResponse {
        $payload = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    /** Resposta 422 de validação com `details` e `errors` (legado Angular). */
    public static function validation(array $errors, ?string $message = null): JsonResponse
    {
        $message ??= 'Os dados enviados são inválidos.';

        return response()->json([
            'code' => 'validation_failed',
            'message' => $message,
            'details' => $errors,
            'errors' => $errors,
        ], 422);
    }

    public static function codeForStatus(int $status): string
    {
        return match ($status) {
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            422 => 'validation_failed',
            429 => 'too_many_requests',
            500 => 'internal_error',
            503 => 'service_unavailable',
            default => 'http_error',
        };
    }

    public static function messageForStatus(int $status): string
    {
        return match ($status) {
            401 => 'Não autenticado.',
            403 => 'Acesso negado.',
            404 => 'Recurso não encontrado.',
            405 => 'Método não permitido.',
            422 => 'Os dados enviados são inválidos.',
            429 => 'Muitas requisições. Tente novamente em instantes.',
            500 => 'Erro interno do servidor.',
            503 => 'Serviço temporariamente indisponível.',
            default => 'Ocorreu um erro na requisição.',
        };
    }

    public static function fromStatus(int $status, ?string $message = null, ?string $code = null, mixed $details = null): JsonResponse
    {
        return self::make(
            $code ?? self::codeForStatus($status),
            $message ?? self::messageForStatus($status),
            $status,
            $details,
        );
    }
}
