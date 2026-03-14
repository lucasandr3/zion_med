<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::orderBy('sort_order')->orderBy('key')->get();
        $trialDays = (int) config('asaas.trial_days', 14);

        return response()->json([
            'data' => $plans->map(fn (Plan $p) => [
                'id' => $p->id,
                'key' => $p->key,
                'name' => $p->name,
                'value' => (float) $p->value,
                'description' => $p->description,
                'sort_order' => $p->sort_order,
                'is_active' => $p->is_active,
                'created_at' => $p->created_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
            ]),
            'trial_days' => $trialDays,
        ]);
    }
}
