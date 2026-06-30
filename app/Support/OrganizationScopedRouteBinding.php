<?php

namespace App\Support;

use App\Models\User;
use App\Services\OrganizationAccessService;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrganizationScopedRouteBinding
{
    public function __construct(
        private readonly OrganizationAccessService $organizationAccess,
        private readonly TenantContextService $tenantContext,
    ) {}

    /**
     * Resolve um modelo pelo ID restrito à organização do contexto API (evita IDOR em route binding).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function resolve(string $modelClass, mixed $value): Model
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }

        $request = request();
        $organizationId = $this->tenantContext->resolveOrganizationId($request);
        if ($organizationId === null) {
            abort(404);
        }

        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        /** @var Model $model */
        $model = $modelClass::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereKey($value)
            ->firstOrFail();

        return $model;
    }
}
