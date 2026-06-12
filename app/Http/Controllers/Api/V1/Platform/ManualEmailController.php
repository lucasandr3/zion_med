<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformManualEmailSendRequest;
use App\Services\PlatformManualEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualEmailController extends Controller
{
    public function __construct(
        private readonly PlatformManualEmailService $manualEmailService,
    ) {}

    public function recipients(): JsonResponse
    {
        return response()->json([
            'data' => $this->manualEmailService->recipientsPayload(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $emails = $this->manualEmailService->paginateHistory(
            (int) $request->input('per_page', 20)
        );

        return response()->json([
            'data' => $emails->map(fn ($email) => $this->manualEmailService->serialize($email)),
            'meta' => [
                'current_page' => $emails->currentPage(),
                'last_page' => $emails->lastPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
            ],
        ]);
    }

    public function send(PlatformManualEmailSendRequest $request): JsonResponse
    {
        try {
            $email = $this->manualEmailService->send($request->user(), $request->validated());
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível enviar o e-mail. Verifique a configuração do Resend e tente novamente.',
            ], 500);
        }

        return response()->json([
            'data' => $this->manualEmailService->serialize($email),
            'message' => 'E-mail enviado com sucesso.',
        ], 201);
    }
}
