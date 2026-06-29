<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class OrganizationAccessService
{
    /**
     * Organizações que o usuário pode acessar (mesmo tenant ou org fixa).
     */
    public function allowedOrganizationsForUser(User $user): Collection
    {
        $tenantId = $user->clinic?->tenant_id;
        if ($tenantId === null) {
            return $user->clinic_id
                ? Organization::query()->where('id', $user->clinic_id)->withCount('users')->get()
                : Organization::query()->whereRaw('0 = 1')->withCount('users')->get();
        }

        return Organization::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->withCount('users')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function allowedOrganizationIdsForUser(User $user): array
    {
        return $this->allowedOrganizationsForUser($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function userMayAccessOrganization(User $user, int $organizationId): bool
    {
        if (! $user->canSwitchClinic()) {
            return (int) $user->clinic_id === $organizationId;
        }

        return in_array($organizationId, $this->allowedOrganizationIdsForUser($user), true);
    }

    /**
     * Resolve a org do contexto API. Ignora header inválido (cross-tenant IDOR).
     */
    public function resolveOrganizationIdForUser(User $user, ?int $headerOrgId): ?int
    {
        $defaultId = $user->clinic_id !== null ? (int) $user->clinic_id : null;

        if (! $user->canSwitchClinic()) {
            return $defaultId;
        }

        if ($headerOrgId !== null && $headerOrgId > 0 && $this->userMayAccessOrganization($user, $headerOrgId)) {
            return $headerOrgId;
        }

        return $defaultId;
    }
}
