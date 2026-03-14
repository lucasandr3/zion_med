<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class UserController extends Controller
{
    /**
     * Lista usuários da clínica.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-users');
        $users = User::withoutGlobalScopes()
            ->where('organization_id', session('current_clinic_id'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Lista roles disponíveis para create/edit (exclui SuperAdmin e PlatformAdmin).
     */
    public function roles(): JsonResponse
    {
        $this->authorize('manage-users');
        $roles = array_values(array_filter(Role::cases(), fn (Role $r) => $r !== Role::SuperAdmin && $r !== Role::PlatformAdmin));

        return response()->json([
            'data' => array_map(fn (Role $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ], $roles),
        ]);
    }

    /**
     * Cria um usuário.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $organizationId = $request->user()->organization_id ?? $request->user()->clinic_id ?? session('current_clinic_id');
        $data['organization_id'] = $organizationId;
        $data['role'] = Role::from($data['role']);
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
        $data['role'] = Role::from($data['role']);
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
