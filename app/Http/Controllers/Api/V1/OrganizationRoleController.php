<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\OrganizationRole;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationRoleController extends Controller
{
    use ResolvesOrganizationContext;

    private function organizationId(Request $request): int
    {
        $id = $this->currentOrganizationId($request);
        if ($id === null || $id === '') {
            abort(422, 'Organização atual não definida.');
        }

        return (int) $id;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->organizationId($request);
        $rows = OrganizationRole::query()
            ->where('organization_id', $orgId)
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (OrganizationRole $r) => $this->serializeList($r)),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->organizationId($request);
        $role = OrganizationRole::query()
            ->where('organization_id', $orgId)
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->serializeDetail($role),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->organizationId($request);
        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]{1,62}$/',
                Rule::notIn(['novo', 'edit', 'criar', 'permissoes', 'papeis']),
                Rule::unique('organization_roles', 'slug')->where('organization_id', $orgId),
            ],
            'label' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(Permission::keys())],
            'clone_from' => ['nullable', 'string', 'max:64'],
        ]);

        $permissions = $validated['permissions'];
        if (! empty($validated['clone_from'])) {
            $base = OrganizationRole::query()
                ->where('organization_id', $orgId)
                ->where('slug', $validated['clone_from'])
                ->first();
            if ($base && is_array($base->permissions)) {
                $permissions = array_values(array_unique(array_merge($base->permissions, $permissions)));
                $permissions = array_values(array_intersect($permissions, Permission::keys()));
            }
        }

        $role = OrganizationRole::create([
            'organization_id' => $orgId,
            'slug' => $validated['slug'],
            'label' => $validated['label'],
            'is_system' => false,
            'is_assignable' => true,
            'permissions' => $permissions,
        ]);

        return response()->json([
            'data' => $this->serializeDetail($role),
        ], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->organizationId($request);
        $role = OrganizationRole::query()
            ->where('organization_id', $orgId)
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(Permission::keys())],
            'is_assignable' => ['sometimes', 'boolean'],
        ]);

        if ($role->slug === 'owner' && $role->is_system) {
            $role->permissions = Permission::ownerDefaults();
        } elseif (isset($validated['permissions'])) {
            $role->permissions = array_values(array_unique(array_intersect($validated['permissions'], Permission::keys())));
        }

        if (array_key_exists('label', $validated)) {
            $role->label = $validated['label'];
        }
        if (array_key_exists('is_assignable', $validated) && ! $role->is_system) {
            $role->is_assignable = $validated['is_assignable'];
        }

        $role->save();

        return response()->json([
            'data' => $this->serializeDetail($role->fresh()),
        ]);
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->organizationId($request);
        $role = OrganizationRole::query()
            ->where('organization_id', $orgId)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($role->is_system) {
            return response()->json(['message' => 'Não é possível excluir um papel do sistema.'], 422);
        }
        if ($role->usersCount() > 0) {
            return response()->json(['message' => 'Existem usuários com este papel. Reatribua antes de excluir.'], 422);
        }
        $role->delete();

        return response()->json(['data' => ['message' => 'Papel removido.']]);
    }

    /** @return array<string, mixed> */
    private function serializeList(OrganizationRole $r): array
    {
        return [
            'slug' => $r->slug,
            'label' => $r->label,
            'is_system' => $r->is_system,
            'is_assignable' => $r->is_assignable,
            'user_count' => $r->usersCount(),
            'permission_count' => is_array($r->permissions) ? count($r->permissions) : 0,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeDetail(OrganizationRole $r): array
    {
        return [
            'slug' => $r->slug,
            'label' => $r->label,
            'is_system' => $r->is_system,
            'is_assignable' => $r->is_assignable,
            'permissions' => is_array($r->permissions) ? $r->permissions : [],
            'user_count' => $r->usersCount(),
        ];
    }
}
