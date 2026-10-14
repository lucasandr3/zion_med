<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    public function segment(string $slug): JsonResponse
    {
        $segments = config('landing.segments', []);
        $data = $segments[$slug] ?? null;
        if (! is_array($data)) {
            return response()->json(['message' => 'Segmento não encontrado.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function howItWorks(string $slug): JsonResponse
    {
        $pages = config('landing.how_it_works', []);
        $data = $pages[$slug] ?? null;
        if (! is_array($data)) {
            return response()->json(['message' => 'Página não encontrada.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Dados públicos da landing (planos e trial).
     */
    public function __invoke(): JsonResponse
    {
        $trialDays = (int) config('asaas.trial_days', 14);
        $plansConfig = Plan::getForConfigCached();
        if ($plansConfig === []) {
            $plansConfig = config('asaas.plans', []);
        }

        $plans = [];
        foreach ($plansConfig as $key => $data) {
            if (! is_array($data)) {
                continue;
            }
            $plans[] = [
                'key' => (string) $key,
                'name' => (string) ($data['name'] ?? $key),
                'value' => (float) ($data['value'] ?? 0),
                'description' => (string) ($data['description'] ?? ''),
                'max_users' => array_key_exists('max_users', $data) ? $data['max_users'] : null,
                'max_organizations_per_tenant' => array_key_exists('max_organizations_per_tenant', $data)
                    ? $data['max_organizations_per_tenant']
                    : null,
                'link_bio_enabled' => (bool) ($data['link_bio_enabled'] ?? true),
            ];
        }

        return response()->json([
            'data' => [
                'trial_days' => $trialDays,
                'plans' => $plans,
                'headline' => config('landing.headline'),
                'subheadline' => config('landing.subheadline'),
                'niches' => config('landing.niches', ['estetica', 'odontologia']),
                'trust_points' => config('landing.trust_points', []),
                'segments' => array_values(config('landing.segments', [])),
                'how_it_works' => array_values(config('landing.how_it_works', [])),
            ],
        ]);
    }
}
