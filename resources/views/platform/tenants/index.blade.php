@extends('layouts.platform')

@section('title', 'Clientes (tenants)')
@section('subtitle', 'Visão geral dos clientes utilizando o Zion Med.')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs text-slate-400">
            {{ $tenants->count() }} {{ $tenants->count() === 1 ? 'cliente' : 'clientes' }} cadastrados.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs text-left text-slate-300">
            <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-[0.16em] text-[10px]">
            <tr>
                <th class="py-2 pr-3">Cliente</th>
                <th class="py-2 px-3">Slug</th>
                <th class="py-2 px-3 text-center">Empresas</th>
                <th class="py-2 pl-3 text-right">Ações</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
            @forelse($tenants as $tenant)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-600/20 text-indigo-300 flex items-center justify-center text-[11px] font-semibold">
                                {{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-50">{{ $tenant->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-[11px] text-slate-400">
                        {{ $tenant->slug }}
                    </td>
                    <td class="py-2.5 px-3 text-center text-[11px]">
                        {{ $tenant->clinics_count }}
                    </td>
                    <td class="py-2.5 pl-3 text-right">
                        <a href="{{ route('platform.tenants.show', $tenant) }}"
                           class="inline-flex items-center gap-1 rounded-lg border border-slate-700/80 px-2.5 py-1.5 text-[11px] font-medium text-slate-100 hover:border-indigo-500/80 hover:text-indigo-200">
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            Ver detalhes
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-6 text-center text-xs text-slate-500">
                        Nenhum tenant cadastrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

