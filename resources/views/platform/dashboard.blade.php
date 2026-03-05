@extends('layouts.platform')

@section('title', 'Visão geral da plataforma')
@section('subtitle', 'Resumo de clientes (tenants), empresas e usuários usando o sistema.')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-2">
                Tenants
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-50">{{ $tenantsCount }}</span>
                <span class="text-xs text-slate-400">clientes cadastrados</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-2">
                Empresas
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-50">{{ $clinicsCount }}</span>
                <span class="text-xs text-slate-400">clínicas / filiais</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-4">
            <div class="text-[11px] font-semibold tracking-[0.18em] text-slate-400 uppercase mb-2">
                Usuários
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-50">{{ $usersCount }}</span>
                <span class="text-xs text-slate-400">contas ativas</span>
            </div>
        </div>
    </div>
@endsection

