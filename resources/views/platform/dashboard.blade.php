@extends('layouts.platform')

@section('title', 'Visão geral da plataforma')
@section('subtitle', 'Resumo de clientes (tenants), empresas e usuários usando o sistema.')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-2" style="color:var(--c-muted)">
                Tenants
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold" style="color:var(--c-text)">{{ $tenantsCount }}</span>
                <span class="text-xs" style="color:var(--c-muted)">clientes cadastrados</span>
            </div>
        </div>
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-2" style="color:var(--c-muted)">
                Empresas
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold" style="color:var(--c-text)">{{ $clinicsCount }}</span>
                <span class="text-xs" style="color:var(--c-muted)">clínicas / filiais</span>
            </div>
        </div>
        <div class="card">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-2" style="color:var(--c-muted)">
                Usuários
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold" style="color:var(--c-text)">{{ $usersCount }}</span>
                <span class="text-xs" style="color:var(--c-muted)">contas ativas</span>
            </div>
        </div>
        <a href="{{ route('platform.leads.index') }}" class="card block no-underline transition-opacity hover:opacity-90" style="color:inherit;text-decoration:none">
            <div class="text-[11px] font-semibold tracking-[0.18em] uppercase mb-2" style="color:var(--c-muted)">
                Leads
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold" style="color:var(--c-text)">{{ $leadsCount }}</span>
                <span class="text-xs" style="color:var(--c-muted)">leads da landing</span>
            </div>
        </a>
    </div>
@endsection
