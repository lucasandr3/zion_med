<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** Resolve organization id atual (token Sanctum → sessão → org do usuário). */
final class OrganizationContext
{
    public static function id(?User $user = null): ?int
    {
        $user ??= Auth::user();

        return $user?->currentOrganizationId();
    }
}
