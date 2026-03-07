<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Events\AuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-users');
        $users = User::withoutGlobalScopes()
            ->where('organization_id', session('current_clinic_id'))
            ->orderBy('name')
            ->get();
        return view('usuarios.index', ['users' => $users]);
    }

    public function create(): View
    {
        $this->authorize('manage-users');
        $roles = array_filter(Role::cases(), fn (Role $r) => $r !== Role::SuperAdmin);
        return view('usuarios.create', ['roles' => array_values($roles)]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['clinic_id'] = $request->user()->clinic_id ?? session('current_clinic_id');
        $data['role'] = Role::from($data['role']);
        $data['active'] = true;
        $data['can_switch_clinic'] = $request->user()->can('grant-clinic-switch')
            ? $request->boolean('can_switch_clinic')
            : false;
        $user = User::create($data);
        Event::dispatch(new AuditEvent('user.created', User::class, $user->id, null, $user->clinic_id, $request->user()->id));
        return redirect()->route('usuarios.index')->with('success', 'Usuário criado.');
    }

    public function edit(Request $request, User $usuario): View
    {
        $this->authorize('update-user', $usuario);
        $roles = array_filter(Role::cases(), fn (Role $r) => $r !== Role::SuperAdmin);
        return view('usuarios.edit', ['usuario' => $usuario, 'roles' => array_values($roles)]);
    }

    public function update(UserUpdateRequest $request, User $usuario): RedirectResponse
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
        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('update-user', $usuario);
        if ($usuario->id === $request->user()->id) {
            return redirect()->route('usuarios.index')->with('error', 'Não é possível desativar a si mesmo.');
        }
        $usuario->update(['active' => false]);
        Event::dispatch(new AuditEvent('user.deactivated', User::class, $usuario->id, null, $usuario->clinic_id, $request->user()->id));
        return redirect()->route('usuarios.index')->with('success', 'Usuário desativado.');
    }
}
