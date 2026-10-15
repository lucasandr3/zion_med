<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::orderBy('sort_order')->orderBy('key')->get();
        $trialDays = (int) config('asaas.trial_days', 14);

        return response()->json([
            'data' => $plans->map(fn (Plan $p) => $this->toArray($p)),
            'trial_days' => $trialDays,
        ]);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['data' => $this->toArray($plan)]);
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! array_key_exists('value_yearly', $data) || $data['value_yearly'] === null) {
            $data['value_yearly'] = round(((float) ($data['value'] ?? 0)) * 10, 2);
        }

        $plan = Plan::create($data);

        return response()->json(['data' => $this->toArray($plan)], 201);
    }

    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        $data = $request->validated();
        unset($data['key']);
        $plan->update($data);

        return response()->json(['data' => $this->toArray($plan->fresh())]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json(['data' => ['message' => 'Plano removido.']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Plan $p): array
    {
        $monthly = (float) $p->value;
        $yearly = $p->value_yearly !== null
            ? (float) $p->value_yearly
            : round($monthly * 10, 2);

        return [
            'id' => $p->id,
            'key' => $p->key,
            'name' => $p->name,
            'value' => $monthly,
            'value_yearly' => $yearly,
            'description' => $p->description,
            'max_users' => $p->max_users,
            'max_organizations_per_tenant' => $p->max_organizations_per_tenant,
            'sort_order' => $p->sort_order,
            'is_active' => $p->is_active,
            'created_at' => $p->created_at?->toIso8601String(),
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }
}
