@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <h1>Usuários</h1>
            </div>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn-primary">
            <span class="material-symbols-outlined" style="font-size:16px">person_add</span>
            Novo usuário
        </a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:30px;height:30px;border-radius:9999px;background:color-mix(in srgb, var(--c-primary) 12%, transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.75rem;font-weight:700;color:var(--c-primary)">
                                {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}
                            </div>
                            <span style="font-weight:500;color:var(--c-text)">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td style="color:var(--c-muted)">{{ $u->email }}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:9999px;background:var(--c-soft);color:var(--c-muted)">
                            <span class="material-symbols-outlined" style="font-size:12px">badge</span>
                            {{ $u->role->label() }}
                        </span>
                    </td>
                    <td>
                        @if($u->active)
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;font-weight:600;color:#22c55e">
                                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">check_circle</span> Ativo
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;font-weight:500;color:var(--c-muted)">
                                <span class="material-symbols-outlined" style="font-size:14px">cancel</span> Inativo
                            </span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:2px">
                            <a href="{{ route('usuarios.edit', $u) }}" data-tooltip="Editar" aria-label="Editar usuário" class="action-btn">
                                <span class="material-symbols-outlined" style="font-size:18px">edit</span>
                            </a>
                            @if($u->active && $u->id !== auth()->id())
                            <form action="{{ route('usuarios.destroy', $u) }}" method="POST" style="display:inline" onsubmit="return confirm('Desativar este usuário?')">
                                @csrf @method('DELETE')
                                <button type="submit" data-tooltip="Desativar" aria-label="Desativar usuário" class="action-btn danger">
                                    <span class="material-symbols-outlined" style="font-size:18px">person_off</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:3rem 1rem">
                        <span class="material-symbols-outlined" style="font-size:36px;color:var(--c-border);display:block;margin-bottom:8px">group</span>
                        <span style="font-size:0.875rem;color:var(--c-muted)">Nenhum usuário cadastrado.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
