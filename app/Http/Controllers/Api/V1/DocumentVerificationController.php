<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DocumentVerificationService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class DocumentVerificationController extends Controller
{
    public function __construct(
        private DocumentVerificationService $verification,
    ) {}

    /**
     * Verificação pública de autenticidade de protocolo (sem autenticação).
     * Não expõe dados clínicos nem PII sensível — só metadados de integridade.
     */
    public function show(string $code): JsonResponse
    {
        try {
            $result = $this->verification->verify($code);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $status = 200;
        if (! ($result['valid'] ?? false)) {
            $status = isset($result['document']) ? 200 : 404;
        }

        return response()->json([
            'valid' => (bool) ($result['valid'] ?? false),
            'message' => $result['reason'] ?? (
                ($result['valid'] ?? false)
                    ? 'Documento autenticado no Gestgo.'
                    : 'Documento não encontrado.'
            ),
            'data' => $result['document'] ?? null,
        ], $status);
    }
}
