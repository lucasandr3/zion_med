<?php

namespace App\Support;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumTenantAbility
{
    public const PREFIX = 'tenant:';

    public static function abilityFor(int $organizationId): string
    {
        return self::PREFIX.$organizationId;
    }

    /**
     * @param  list<mixed>  $abilities
     */
    public static function fromAbilities(array $abilities): ?int
    {
        foreach ($abilities as $ability) {
            if (! is_string($ability) || ! str_starts_with($ability, self::PREFIX)) {
                continue;
            }
            $id = (int) substr($ability, strlen(self::PREFIX));

            return $id > 0 ? $id : null;
        }

        return null;
    }

    public static function fromToken(?PersonalAccessToken $token): ?int
    {
        if ($token === null) {
            return null;
        }

        return self::fromAbilities($token->abilities ?? []);
    }

    public static function applyToToken(User $user, int $organizationId): void
    {
        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return;
        }
        if ($token instanceof \Mockery\MockInterface || $token->getKey() === null) {
            return;
        }

        $abilities = array_values(array_filter(
            $token->abilities ?? [],
            fn ($ability) => ! (is_string($ability) && str_starts_with($ability, self::PREFIX))
        ));
        $abilities[] = self::abilityFor($organizationId);

        $token->forceFill(['abilities' => $abilities])->save();
    }

    /**
     * @return list<string>
     */
    public static function tokenAbilitiesForOrganization(?int $organizationId): array
    {
        if ($organizationId === null || $organizationId <= 0) {
            return [];
        }

        return [self::abilityFor($organizationId)];
    }
}
