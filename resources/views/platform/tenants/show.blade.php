@extends('layouts.platform')

@section('title', 'Tenant: '.$tenant->name)
@section('subtitle', 'Detalhes do cliente e empresas vinculadas.')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-1" style="color:var(--c-muted)">
                Cliente
            </div>
            <div class="text-sm font-semibold" style="color:var(--c-text)">{{ $tenant->name }}</div>
            <div class="text-[11px] mt-1" style="color:var(--c-muted)">Slug: {{ $tenant->slug }}</div>
        </div>
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-1" style="color:var(--c-muted)">
                Empresas
            </div>
            <div class="text-2xl font-semibold" style="color:var(--c-text)">{{ $clinics->count() }}</div>
        </div>
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-1" style="color:var(--c-muted)">
                Atalho
            </div>
            <p class="text-[11px]" style="color:var(--c-muted)">
                Use esta visão apenas para gestão executiva. O acesso operacional às empresas continua sendo feito pelo app do tenant.
            </p>
        </div>
    </div>

    <h2 class="text-xs font-semibold tracking-[0.18em] uppercase mb-3" style="color:var(--c-muted)">
        Empresas do tenant
    </h2>

    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--c-border);background:var(--c-surface)">
        <table class="platform-table">
            <thead>
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Plano</th>
                <th class="py-2 px-3">Status ass.</th>
                <th class="py-2 px-3">Status cobrança</th>
                <th class="py-2 pl-3 text-center">Usuários</th>
            </tr>
            </thead>
            <tbody>
            @forelse($clinics as $clinic)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold" style="color:var(--c-text)">{{ $clinic->name }}</div>
                        @if($clinic->address)
                            <div class="text-[11px] cell-muted">{{ $clinic->address }}</div>
                        @endif
                    </td>
                    <td class="py-2.5 px-3">{{ $clinic->plan_key ?? '—' }}</td>
                    <td class="py-2.5 px-3">{{ $clinic->subscription_status ?? '—' }}</td>
                    <td class="py-2.5 px-3">{{ $clinic->billing_status ?? '—' }}</td>
                    <td class="py-2.5 pl-3 text-center">{{ $clinic->users_count ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-xs cell-muted">
                        Nenhuma empresa vinculada a este tenant.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
