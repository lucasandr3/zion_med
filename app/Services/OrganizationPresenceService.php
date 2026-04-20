<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationPresence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationPresenceService
{
    public function join(int $organizationId): void
    {
        $organization = Organization::query()->find($organizationId);
        if (! $organization) {
            return;
        }

        $now = now();

        DB::transaction(function () use ($organization, $now): void {
            $row = OrganizationPresence::query()
                ->where('organization_id', $organization->id)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                OrganizationPresence::query()->create([
                    'organization_id' => $organization->id,
                    'organization_name' => (string) $organization->name,
                    'active_sessions' => 1,
                    'last_seen_at' => $now,
                ]);

                return;
            }

            $row->organization_name = (string) $organization->name;
            $row->active_sessions = (int) $row->active_sessions + 1;
            $row->last_seen_at = $now;
            $row->save();
        });
    }

    public function leave(int $organizationId): void
    {
        DB::transaction(function () use ($organizationId): void {
            $row = OrganizationPresence::query()
                ->where('organization_id', $organizationId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                return;
            }

            if ((int) $row->active_sessions <= 1) {
                $row->delete();

                return;
            }

            $row->active_sessions = (int) $row->active_sessions - 1;
            $row->last_seen_at = now();
            $row->save();
        });
    }

    /**
     * @return list<int>
     */
    public function organizationIdsAllowedForUser(User $user): array
    {
        if ($user->isPlatformAdmin()) {
            return [];
        }

        $tenantId = $user->clinic?->tenant_id;
        if ($tenantId === null) {
            if ($user->clinic_id) {
                return [(int) $user->clinic_id];
            }

            return [];
        }

        return Organization::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function userMayAccessOrganization(User $user, int $organizationId): bool
    {
        return in_array($organizationId, $this->organizationIdsAllowedForUser($user), true);
    }

    /** Remove linhas muito antigas (ex.: aba fechada sem beacon). */
    public function pruneStale(int $maxAgeHours = 48): int
    {
        $cutoff = now()->subHours($maxAgeHours);

        return OrganizationPresence::query()
            ->where('updated_at', '<', $cutoff)
            ->delete();
    }
}
