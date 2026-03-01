@extends('layouts.app')

@section('title', 'Novo usuário')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">person_add</span>
            </div>
            <div>
                <h1>Novo usuário</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('usuarios.store') }}" method="POST" style="display:flex;flex-direction:column;gap:1.25rem">
            @csrf
            <div>
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Nome completo">
                @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="usuario@email.com">
                @error('email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label class="form-label">Senha</label>
                    <input type="password" name="password" required class="form-input" placeholder="••••••••">
                    @error('password')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Confirmar senha</label>
                    <input type="password" name="password_confirmation" required class="form-input" placeholder="••••••••">
                </div>
            </div>
            <div>
                <label class="form-label">Perfil</label>
                <select name="role" required class="form-select">
                    @foreach($roles as $r)
                        <option value="{{ $r->value }}" {{ old('role') === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                    @endforeach
                </select>
                @error('role')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            @can('grant-clinic-switch')
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="can_switch_clinic" value="0">
                <input type="checkbox" name="can_switch_clinic" value="1" {{ old('can_switch_clinic') ? 'checked' : '' }} class="form-checkbox">
                <span class="form-label" style="margin:0">Pode acessar todas as clínicas</span>
            </label>
            <p style="font-size:0.75rem;color:var(--c-muted);margin-top:-0.5rem">Este usuário poderá trocar entre as clínicas do sistema.</p>
            @endcan
            <div style="display:flex;align-items:center;gap:12px;padding-top:0.5rem">
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">save</span>
                    Criar usuário
                </button>
                <a href="{{ route('usuarios.index') }}" style="font-size:0.8125rem;color:var(--c-muted);text-decoration:none">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
