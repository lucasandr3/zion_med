<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    /**
     * Retorna o status atual do serviço (público, sem autenticação).
     */
    public function index(): JsonResponse
    {
        return response()->json(PlatformSetting::getServiceStatusPayload());
    }
}
