<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantsController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::withCount('clinics')->orderBy('name')->get();

        return response()->json([
            'data' => $tenants->map(fn (Tenant $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'clinics_count' => $t->clinics_count,
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['clinics' => fn ($q) => $q->orderBy('name')]);
        $clinics = $tenant->clinics()->withCount('users')->orderBy('name')->get();

        return response()->json([
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                ],
                'clinics' => $clinics->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'address' => $c->address,
                    'plan_key' => $c->plan_key,
                    'subscription_status' => $c->subscription_status,
                    'billing_status' => $c->billing_status,
                    'users_count' => $c->users_count,
                ]),
            ],
        ]);
    }
}
