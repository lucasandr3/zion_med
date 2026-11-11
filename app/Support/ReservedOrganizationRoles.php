<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Slugs que não podem ser criados/atribuídos via API de papéis da clínica.
 * platform_admin só existe com organization_id nulo (conta da plataforma).
 */
final class ReservedOrganizationRoles
{
    /** @return list<string> */
    public static function platformOnly(): array
    {
        return ['platform_admin'];
    }

    /** @return list<string> */
    public static function routeSlugs(): array
    {
        return ['novo', 'edit', 'criar', 'permissoes', 'papeis'];
    }

    /** @return list<string> */
    public static function blockedCustomSlugs(): array
    {
        return array_values(array_unique([
            ...self::routeSlugs(),
            ...self::platformOnly(),
        ]));
    }
}
