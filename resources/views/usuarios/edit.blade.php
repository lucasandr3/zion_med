@extends('layouts.app')

@section('title', 'Editar usuário')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">manage_accounts</span>
            </div>
            <div>
                <h1>Editar usuário</h1>
            </div>
        </div>
    </div>

    <div class="card" style="max-width:560px">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST" style="display:flex;flex-direction:column;gap:1.25rem">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required class="form-input">
                @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required class="form-input">
                @error('email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label class="form-label">Nova senha <span style="font-weight:400;color:var(--c-muted)">(em branco para manter)</span></label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••">
                    @error('password')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Confirmar nova senha</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="••••••••">
                </div>
            </div>
            <div>
                <label class="form-label">Perfil</label>
                <select name="role" required class="form-select">
                    @foreach($roles as $r)
                        <option value="{{ $r->value }}" {{ old('role', $usuario->role->value) === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>
            @can('grant-clinic-switch', $usuario)
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="can_switch_clinic" value="0">
                <input type="checkbox" name="can_switch_clinic" value="1" {{ old('can_switch_clinic', $usuario->can_switch_clinic) ? 'checked' : '' }} class="form-checkbox">
                <span class="form-label" style="margin:0">Pode acessar todas as clínicas</span>
            </label>
            <p style="font-size:0.75rem;color:var(--c-muted);margin-top:-0.5rem">Este usuário poderá trocar entre as clínicas do sistema.</p>
            @endcan
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" {{ old('active', $usuario->active) ? 'checked' : '' }} class="form-checkbox">
                <span class="form-label" style="margin:0">Ativo</span>
            </label>
            <div style="display:flex;align-items:center;gap:12px;padding-top:0.5rem">
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">save</span>
                    Salvar alterações
                </button>
                <a href="{{ route('usuarios.index') }}" style="font-size:0.8125rem;color:var(--c-muted);text-decoration:none">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
