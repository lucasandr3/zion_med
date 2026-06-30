<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AuditEvent;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class UserController extends Controller
{
    use ResolvesOrganizationContext;

    /**
     * Lista usuários da clínica.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->currentOrganizationId($request);
        $users = User::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Lista papéis atribuíveis da organização atual (organization_roles).
     */
    public function roles(Request $request): JsonResponse
    {
        $this->authorize('manage-users');
        $orgId = $this->currentOrganizationId($request);
        if ($orgId === null || $orgId === '') {
            return response()->json(['data' => []]);
        }
        $roles = OrganizationRole::query()
            ->where('organization_id', (int) $orgId)
            ->where('is_assignable', true)
            ->orderBy('label')
            ->get(['slug', 'label']);

        return response()->json([
            'data' => $roles->map(fn (OrganizationRole $r) => [
                'value' => $r->slug,
                'label' => $r->label,
            ])->values()->all(),
        ]);
    }

    /**
     * Cria um usuário.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $organizationId = $this->currentOrganizationId($request);
        $organization = $organizationId ? Organization::query()->find((int) $organizationId) : null;
        if ($organization && ! $organization->canAddAnotherUser()) {
            return response()->json([
                'message' => 'O plano atual atingiu o limite de usuários. Faça upgrade do plano ou desative outro usuário antes de adicionar um novo.',
            ], 422);
        }
        $data['organization_id'] = $organizationId;
        $data['active'] = true;
        $data['can_switch_clinic'] = $request->user()->can('grant-clinic-switch')
            ? $request->boolean('can_switch_clinic')
            : false;
        $user = User::create($data);
        Event::dispatch(new AuditEvent('user.created', User::class, $user->id, null, $organizationId, $request->user()->id));

        return response()->json([
            'data' => new UserResource($user->fresh()),
        ], 201);
    }

    /**
     * Exibe um usuário.
     */
    public function show(User $usuario): JsonResponse
    {
        $this->authorize('update-user', $usuario);

        return response()->json([
            'data' => new UserResource($usuario),
        ]);
    }

    /**
     * Atualiza um usuário.
     */
    public function update(UserUpdateRequest $request, User $usuario): JsonResponse
    {
        $data = $request->validated();
        if ($request->filled('password')) {
            $data['password'] = $data['password'];
        } else {
            unset($data['password']);
        }
        $data['active'] = $request->boolean('active', true);
        if ($request->user()->can('grant-clinic-switch', $usuario)) {
            $data['can_switch_clinic'] = $request->boolean('can_switch_clinic');
        } else {
            unset($data['can_switch_clinic']);
        }
        $usuario->update($data);

        return response()->json([
            'data' => new UserResource($usuario->fresh()),
        ]);
    }

    /**
     * Desativa um usuário (soft: active = false).
     */
    public function destroy(Request $request, User $usuario): JsonResponse
    {
        $this->authorize('update-user', $usuario);
        if ($usuario->id === $request->user()->id) {
            return response()->json([
                'message' => 'Não é possível desativar a si mesmo.',
            ], 422);
        }
        $usuario->update(['active' => false]);
        Event::dispatch(new AuditEvent('user.deactivated', User::class, $usuario->id, null, $usuario->clinic_id, $request->user()->id));

        return response()->json([
            'data' => new UserResource($usuario->fresh()),
        ]);
    }
}
