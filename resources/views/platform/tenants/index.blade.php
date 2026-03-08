@extends('layouts.platform')

@section('title', 'Clientes (tenants)')
@section('subtitle', 'Visão geral dos clientes utilizando o Zion Med.')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs" style="color:var(--c-muted)">
            {{ $tenants->count() }} {{ $tenants->count() === 1 ? 'cliente' : 'clientes' }} cadastrados.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--c-border);background:var(--c-surface)">
        <table class="platform-table">
            <thead>
            <tr>
                <th class="py-2 pr-3">Cliente</th>
                <th class="py-2 px-3">Slug</th>
                <th class="py-2 px-3 text-center">Empresas</th>
                <th class="py-2 pl-3 text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-semibold"
                                 style="background:color-mix(in srgb, var(--c-primary) 18%, transparent);color:var(--c-primary)">
                                {{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}
                            </div>
                            <div class="text-xs font-semibold" style="color:var(--c-text)">{{ $tenant->name }}</div>
                        </div>
                    </td>
                    <td class="py-2.5 px-3 cell-muted">{{ $tenant->slug }}</td>
                    <td class="py-2.5 px-3 text-center">{{ $tenant->clinics_count }}</td>
                    <td class="py-2.5 pl-3 text-right">
                        <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn-cell">
                            <span class="material-symbols-outlined" style="font-size:14px">open_in_new</span>
                            Ver detalhes
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-6 text-center text-xs cell-muted">
                        Nenhum tenant cadastrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
