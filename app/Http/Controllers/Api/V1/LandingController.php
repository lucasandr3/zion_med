<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    /**
     * Dados públicos da landing (planos e trial) — mesma fonte que a view landing.blade.php (config asaas).
     */
    public function __invoke(): JsonResponse
    {
        $plansConfig = config('asaas.plans', []);
        $trialDays = (int) config('asaas.trial_days', 14);

        $plans = [];
        foreach ($plansConfig as $key => $data) {
            $plans[] = [
                'key' => $key,
                'name' => $data['name'] ?? $key,
                'value' => (float) ($data['value'] ?? 0),
                'description' => $data['description'] ?? '',
            ];
        }

        return response()->json([
            'data' => [
                'trial_days' => $trialDays,
                'plans' => $plans,
            ],
        ]);
    }
}
