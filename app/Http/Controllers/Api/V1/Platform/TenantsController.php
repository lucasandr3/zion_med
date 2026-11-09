<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\TenantDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantsController extends Controller
{
    public function __construct(
        private TenantDeletionService $tenantDeletion,
    ) {}

    public function index(): JsonResponse
    {
        $tenants = Tenant::query()
            ->withCount('clinics')
            ->with(['clinics' => fn ($q) => $q
                ->select([
                    'id',
                    'tenant_id',
                    'subscription_status',
                    'billing_status',
                    'plan_key',
                    'trial_ends_at',
                ])
                ->withCount('users')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $tenants->map(fn (Tenant $t) => $this->mapTenantListItem($t)),
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $clinics = $tenant->clinics()
            ->withCount(['users', 'people', 'formSubmissions'])
            ->with(['subscriptions' => fn ($q) => $q->orderByDesc('created_at')])
            ->orderBy('name')
            ->get();

        $summary = [
            'clinics_count' => $clinics->count(),
            'users_count' => (int) $clinics->sum('users_count'),
            'people_count' => (int) $clinics->sum('people_count'),
            'form_submissions_count' => (int) $clinics->sum('form_submissions_count'),
        ];

        return response()->json([
            'data' => [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                    'updated_at' => $tenant->updated_at?->toIso8601String(),
                ],
                'summary' => $summary,
                'clinics' => $clinics->map(fn (Organization $c) => $this->mapClinicDetail($c)),
            ],
        ]);
    }

    /**
     * Exclui o tenant e dados vinculados (empresas, usuários, fichas, etc.).
     * Exige confirm_name igual ao nome do tenant para evitar exclusão acidental.
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'confirm_name' => ['required', 'string', 'max:255'],
        ], [
            'confirm_name.required' => 'Digite o nome do cliente para confirmar a exclusão.',
        ]);

        $confirmName = trim((string) $validated['confirm_name']);
        if ($confirmName === '' || strcasecmp($confirmName, (string) $tenant->name) !== 0) {
            throw ValidationException::withMessages([
                'confirm_name' => ['O nome informado não confere com o cliente. Digite exatamente: '.$tenant->name],
            ]);
        }

        $result = $this->tenantDeletion->delete($tenant);

        return response()->json([
            'data' => [
                'message' => 'Cliente e dados vinculados removidos.',
                'deleted' => $result,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTenantListItem(Tenant $tenant): array
    {
        $clinics = $tenant->clinics;
        $usersCount = (int) $clinics->sum('users_count');

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'clinics_count' => $tenant->clinics_count,
            'users_count' => $usersCount,
            'created_at' => $tenant->created_at?->toIso8601String(),
            'updated_at' => $tenant->updated_at?->toIso8601String(),
            'subscription_status' => $this->aggregateSubscriptionStatus($clinics),
            'billing_status' => $this->aggregateBillingStatus($clinics),
            'active_plans' => $clinics
                ->pluck('plan_key')
                ->filter(fn ($k) => $k !== null && $k !== '')
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapClinicDetail(Organization $clinic): array
    {
        /** @var Subscription|null $latestSubscription */
        $latestSubscription = $clinic->subscriptions->first();
        $planDef = $clinic->planDefinition();
        $planLimits = $clinic->planLimitsForApi();

        return [
            'id' => $clinic->id,
            'name' => $clinic->name,
            'slug' => $clinic->slug,
            'niche' => $clinic->niche,
            'address' => $clinic->address,
            'phone' => $clinic->phone,
            'contact_email' => $clinic->contact_email,
            'notification_email' => $clinic->notification_email,
            'billing_name' => $clinic->billing_name,
            'billing_email' => $clinic->billing_email,
            'billing_document' => $clinic->billing_document,
            'asaas_customer_id' => $clinic->asaas_customer_id,
            'plan_key' => $clinic->plan_key,
            'plan_name' => $planDef['name'] ?? null,
            'plan_value' => isset($planDef['value']) ? (float) $planDef['value'] : null,
            'subscription_status' => $clinic->subscription_status,
            'billing_status' => $clinic->billing_status,
            'trial_ends_at' => $clinic->trial_ends_at?->toIso8601String(),
            'grace_ends_at' => $clinic->grace_ends_at?->toIso8601String(),
            'can_access_app' => $clinic->canAccessTenantAppFeatures(),
            'is_on_trial' => $clinic->isOnTrial(),
            'users_count' => $clinic->users_count,
            'people_count' => $clinic->people_count,
            'form_submissions_count' => $clinic->form_submissions_count,
            'max_users' => $planLimits['max_users'],
            'max_organizations_per_tenant' => $planLimits['max_organizations_per_tenant'],
            'whatsapp_notifications_enabled' => (bool) $clinic->whatsapp_notifications_enabled,
            'feegow_enabled' => (bool) $clinic->feegow_enabled,
            'feegow_last_status' => $clinic->feegow_last_status,
            'created_at' => $clinic->created_at?->toIso8601String(),
            'updated_at' => $clinic->updated_at?->toIso8601String(),
            'latest_subscription' => $latestSubscription ? [
                'id' => $latestSubscription->id,
                'asaas_subscription_id' => $latestSubscription->asaas_subscription_id,
                'plan_key' => $latestSubscription->plan_key,
                'status' => $latestSubscription->status,
                'current_period_end' => $latestSubscription->current_period_end?->toIso8601String(),
                'next_due_date' => $latestSubscription->next_due_date?->toIso8601String(),
                'created_at' => $latestSubscription->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @param  iterable<int, Organization>  $clinics
     */
    private function aggregateSubscriptionStatus(iterable $clinics): ?string
    {
        $priority = ['past_due', 'inactive', 'trial', 'active'];
        $statuses = collect($clinics)
            ->pluck('subscription_status')
            ->filter(fn ($s) => $s !== null && $s !== '')
            ->map(fn ($s) => strtolower((string) $s))
            ->unique();

        foreach ($priority as $status) {
            if ($statuses->contains($status)) {
                return $status;
            }
        }

        return $statuses->first();
    }

    /**
     * @param  iterable<int, Organization>  $clinics
     */
    private function aggregateBillingStatus(iterable $clinics): ?string
    {
        $priority = ['blocked', 'attention', 'ok'];
        $statuses = collect($clinics)
            ->pluck('billing_status')
            ->filter(fn ($s) => $s !== null && $s !== '')
            ->map(fn ($s) => strtolower((string) $s))
            ->unique();

        foreach ($priority as $status) {
            if ($statuses->contains($status)) {
                return $status;
            }
        }

        return $statuses->first();
    }
}
