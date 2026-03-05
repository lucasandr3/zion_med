@extends('layouts.platform')

@section('title', 'Tenant: '.$tenant->name)
@section('subtitle', 'Detalhes do cliente e empresas vinculadas.')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-1">
                Cliente
            </div>
            <div class="text-sm font-semibold text-slate-50">{{ $tenant->name }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Slug: {{ $tenant->slug }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-1">
                Empresas
            </div>
            <div class="text-2xl font-semibold text-slate-50">{{ $clinics->count() }}</div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-1">
                Atalho
            </div>
            <p class="text-[11px] text-slate-400">
                Use esta visão apenas para gestão executiva. O acesso operacional às empresas continua sendo feito pelo app do tenant.
            </p>
        </div>
    </div>

    <h2 class="text-xs font-semibold tracking-[0.18em] text-slate-400 uppercase mb-3">
        Empresas do tenant
    </h2>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs text-left text-slate-300">
            <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-[0.16em] text-[10px]">
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Plano</th>
                <th class="py-2 px-3">Status ass.</th>
                <th class="py-2 px-3">Status cobrança</th>
                <th class="py-2 pl-3 text-center">Usuários</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
            @forelse($clinics as $clinic)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold text-slate-50">{{ $clinic->name }}</div>
                        @if($clinic->address)
                            <div class="text-[11px] text-slate-500">{{ $clinic->address }}</div>
                        @endif
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $clinic->plan_key ?? '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $clinic->subscription_status ?? '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $clinic->billing_status ?? '—' }}
                    </td>
                    <td class="py-2.5 pl-3 text-center text-[11px]">
                        {{ $clinic->users_count ?? 0 }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-xs text-slate-500">
                        Nenhuma empresa vinculada a este tenant.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

