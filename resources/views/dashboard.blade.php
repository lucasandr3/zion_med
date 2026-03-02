@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    @if($semClinica ?? false)
    <div class="card mb-6 flex items-center gap-4" style="padding:1rem 1.25rem;background:var(--c-soft);border:1px solid var(--c-border)">
        <span class="material-symbols-outlined shrink-0" style="font-size:28px;color:var(--c-muted)">business</span>
        <div class="min-w-0">
            <p class="text-sm font-medium" style="color:var(--c-text)">Selecione uma clínica para ver o resumo</p>
            <p class="text-xs mt-0.5" style="color:var(--c-muted)">Os números e atalhos do dashboard são exibidos por clínica.</p>
        </div>
        <a href="{{ route('clinica.escolher') }}" class="btn-primary shrink-0">Escolher empresa</a>
    </div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        {{-- Protocolos pendentes --}}
        @can('view-submissions')
        <a href="{{ route('protocolos.index', ['status' => 'pending', 'data_inicio' => today()->format('Y-m-d'), 'data_fim' => today()->format('Y-m-d')]) }}"
           class="card flex items-start justify-between no-underline transition-opacity hover:opacity-90"
           style="text-decoration:none;color:inherit"
           aria-label="Ver {{ $pendentesHoje }} protocolos pendentes de hoje">
        @else
        <div class="card flex items-start justify-between">
        @endcan
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color:var(--c-muted)">Pendentes hoje</p>
                <p class="text-3xl font-bold" style="color:var(--c-text)">{{ $pendentesHoje }}</p>
                <p class="text-xs mt-1" style="color:var(--c-muted)">Protocolos aguardando revisão</p>
            </div>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ml-3"
                 style="background:rgba(251,191,36,0.12)">
                <span class="material-symbols-outlined" style="font-size:19px;color:#fbbf24">pending_actions</span>
            </div>
        @can('view-submissions')
        </a>
        @else
        </div>
        @endcan

        {{-- Templates --}}
        <div class="card flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color:var(--c-muted)">Templates</p>
                <p class="text-3xl font-bold" style="color:var(--c-text)">{{ $ultimosTemplates->count() }}</p>
                <p class="text-xs mt-1" style="color:var(--c-muted)">Criados recentemente</p>
            </div>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ml-3"
                 style="background:rgba(var(--c-primary) / 0.12, var(--c-primary), 0.12)">
                <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--c-primary) 12%, transparent)">
                    <span class="material-symbols-outlined" style="font-size:19px;color:var(--c-primary)">description</span>
                </div>
            </div>
        </div>

        {{-- Últimos 7 / 30 dias --}}
        @php
            $urlProtocolos7d = route('protocolos.index', [
                'data_inicio' => now()->subDays(7)->format('Y-m-d'),
                'data_fim' => today()->format('Y-m-d'),
            ]);
        @endphp
        @can('view-submissions')
        <a href="{{ $urlProtocolos7d }}"
           class="card flex items-start justify-between no-underline transition-opacity hover:opacity-90"
           style="text-decoration:none;color:inherit"
           aria-label="Ver protocolos dos últimos 7 dias">
        @else
        <div class="card flex items-start justify-between">
        @endcan
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color:var(--c-muted)">Últimos dias</p>
                <p class="text-2xl font-bold" style="color:var(--c-text)">{{ $ultimos7Dias ?? 0 }} <span class="text-lg font-normal text-muted">/ 7 dias</span></p>
                <p class="text-sm mt-1" style="color:var(--c-muted)">{{ $ultimos30Dias ?? 0 }} nos últimos 30 dias</p>
            </div>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ml-3" style="background:rgba(59,130,246,0.12)">
                <span class="material-symbols-outlined" style="font-size:19px;color:#3b82f6">trending_up</span>
            </div>
        @can('view-submissions')
        </a>
        @else
        </div>
        @endcan

        {{-- Links para enviar (card único com conteúdo condicional) --}}
        @php $linksCount = $linksPublicosCount ?? 0; @endphp
        <a href="{{ route('links-publicos.index') }}"
           class="card flex items-start justify-between no-underline transition-opacity hover:opacity-90"
           style="text-decoration:none;color:inherit"
           aria-label="{{ $linksCount ? "Ver {$linksCount} links para enviar" : 'Acessar links para enviar' }}">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color:var(--c-muted)">Links para enviar</p>
                <p class="text-3xl font-bold" style="color:var(--c-text)">{{ $linksCount }}</p>
                <p class="text-xs mt-1" style="color:var(--c-muted)">{{ $linksCount ? 'Formulários com link público' : 'Copiar e enviar pelo WhatsApp' }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ml-3" style="background:{{ $linksCount ? 'rgba(34,197,94,0.12)' : 'var(--c-soft)' }}">
                <span class="material-symbols-outlined" style="font-size:19px;color:{{ $linksCount ? '#22c55e' : 'var(--c-muted)' }}">link</span>
            </div>
        </a>
    </div>

    {{-- Protocolos por situação --}}
    @if(!empty($porStatus))
    @php
        $totalStatus = array_sum($porStatus);
        $pending = $porStatus['pending'] ?? 0;
        $approved = $porStatus['approved'] ?? 0;
        $rejected = $porStatus['rejected'] ?? 0;
    @endphp
    <div class="card mb-6" style="padding:1.25rem 1.5rem">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">bar_chart</span>
            <h2 class="text-sm font-semibold" style="color:var(--c-text)">Protocolos por situação</h2>
        </div>
        <div class="space-y-3">
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-muted">Pendente</span>
                    <span style="color:var(--c-text)">{{ $pending }}</span>
                </div>
                <div class="h-2 rounded-full overflow-hidden bg-content/10">
                    <div class="h-full rounded-full bg-amber-500" style="width:{{ $totalStatus ? round($pending / $totalStatus * 100) : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-muted">Aprovado</span>
                    <span style="color:var(--c-text)">{{ $approved }}</span>
                </div>
                <div class="h-2 rounded-full overflow-hidden bg-content/10">
                    <div class="h-full rounded-full bg-green-500" style="width:{{ $totalStatus ? round($approved / $totalStatus * 100) : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-muted">Reprovado</span>
                    <span style="color:var(--c-text)">{{ $rejected }}</span>
                </div>
                <div class="h-2 rounded-full overflow-hidden bg-content/10">
                    <div class="h-full rounded-full bg-red-500" style="width:{{ $totalStatus ? round($rejected / $totalStatus * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Ações rápidas (full width) --}}
    <div class="card w-full mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color:var(--c-muted)">Acesso rápido</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('links-publicos.index') }}" class="btn-ghost btn-default-bg">
                <span class="material-symbols-outlined" style="font-size:16px">link</span>
                Links para enviar
            </a>
            @can('manage-templates')
            <a href="{{ route('templates.create') }}" class="btn-primary">
                <span class="material-symbols-outlined" style="font-size:16px">add</span>
                Novo template
            </a>
            @endcan
            @can('view-submissions')
            <a href="{{ route('protocolos.index') }}" class="btn-ghost btn-default-bg">
                <span class="material-symbols-outlined" style="font-size:16px">inbox</span>
                Protocolos
            </a>
            @endcan
        </div>
    </div>

    {{-- Últimos templates --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div class="flex items-center justify-between gap-2 px-5 py-3.5" style="border-bottom:1px solid var(--c-border)">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--c-muted)">history</span>
                <h2 class="text-sm font-semibold" style="color:var(--c-text)">Últimos templates</h2>
            </div>
            @if($ultimosTemplates->isNotEmpty())
            <a href="{{ route('templates.index') }}" class="text-xs font-medium" style="color:var(--c-primary);text-decoration:none">Ver todos</a>
            @endif
        </div>
        @forelse($ultimosTemplates as $t)
        <a href="{{ route('templates.campos.index', $t) }}"
           class="flex items-center justify-between px-5 py-3.5 transition-colors group"
           style="border-bottom:1px solid var(--c-border)"
           onmouseover="this.style.background='var(--c-soft)'"
           onmouseout="this.style.background='transparent'"
           aria-label="Editar template {{ $t->name }}">
            <div class="flex items-center gap-3 min-w-0">
                <span class="material-symbols-outlined shrink-0" style="font-size:17px;color:var(--c-muted)">description</span>
                <div class="min-w-0">
                    <span class="text-sm font-medium block truncate" style="color:var(--c-text)">{{ $t->name }}</span>
                    <span class="text-xs" style="color:var(--c-muted)">{{ $t->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <span class="material-symbols-outlined shrink-0" style="font-size:17px;color:var(--c-muted)">chevron_right</span>
        </a>
        @empty
        <div class="px-5 py-12 text-center">
            <span class="material-symbols-outlined block mb-2" style="font-size:36px;color:var(--c-border)">description</span>
            <p class="text-sm" style="color:var(--c-muted)">Nenhum template ainda.</p>
        </div>
        @endforelse
    </div>

@endsection
