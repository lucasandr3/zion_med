<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Status — {{ $service_name }}</title>
    <meta name="description" content="Acompanhe o status dos serviços {{ $service_name }} em tempo real." />

    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                extend: {}
            }
        }
    </script>
    <style>
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes slide-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .slide-up { animation: slide-up .5s ease-out both; }
        .slide-up-d1 { animation-delay: .08s; }
        .slide-up-d2 { animation-delay: .16s; }
        .slide-up-d3 { animation-delay: .24s; }
        .slide-up-d4 { animation-delay: .32s; }
    </style>
</head>

@php
    $frontUrl = config('app.frontend_url') ? rtrim(config('app.frontend_url'), '/') : null;
    $statusMap = [
        'operational'  => ['label' => 'Todos os sistemas operacionais', 'short' => 'Operacional',  'color' => 'emerald', 'icon' => 'check'],
        'degraded'     => ['label' => 'Desempenho degradado',           'short' => 'Degradado',     'color' => 'amber',   'icon' => 'alert'],
        'outage'       => ['label' => 'Interrupção nos serviços',       'short' => 'Indisponível',  'color' => 'red',     'icon' => 'x'],
        'maintenance'  => ['label' => 'Manutenção em andamento',        'short' => 'Manutenção',    'color' => 'indigo',  'icon' => 'wrench'],
    ];

    $severityMap = [
        'none'     => ['label' => 'Nenhuma',  'badge' => 'bg-slate-100 text-slate-600',    'bar' => 'bg-slate-300'],
        'low'      => ['label' => 'Baixa',    'badge' => 'bg-sky-100 text-sky-700',        'bar' => 'bg-sky-400'],
        'medium'   => ['label' => 'Média',    'badge' => 'bg-amber-100 text-amber-700',    'bar' => 'bg-amber-400'],
        'high'     => ['label' => 'Alta',      'badge' => 'bg-orange-100 text-orange-700',  'bar' => 'bg-orange-500'],
        'critical' => ['label' => 'Crítica',   'badge' => 'bg-red-100 text-red-700',        'bar' => 'bg-red-500'],
    ];

    $s = $statusMap[$status] ?? $statusMap['operational'];
    $sev = $severityMap[$severity] ?? $severityMap['none'];
    $colorMap = [
        'emerald' => ['banner' => 'from-emerald-600 to-emerald-500', 'dot' => 'bg-emerald-400', 'ring' => 'ring-emerald-500/20', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
        'amber'   => ['banner' => 'from-amber-500 to-amber-400',    'dot' => 'bg-amber-400',   'ring' => 'ring-amber-500/20',   'text' => 'text-amber-700',   'bg' => 'bg-amber-50',   'border' => 'border-amber-200'],
        'red'     => ['banner' => 'from-red-600 to-red-500',        'dot' => 'bg-red-400',     'ring' => 'ring-red-500/20',     'text' => 'text-red-700',     'bg' => 'bg-red-50',     'border' => 'border-red-200'],
        'indigo'  => ['banner' => 'from-indigo-600 to-indigo-500',  'dot' => 'bg-indigo-400',  'ring' => 'ring-indigo-500/20',  'text' => 'text-indigo-700',  'bg' => 'bg-indigo-50',  'border' => 'border-indigo-200'],
    ];
    $c = $colorMap[$s['color']];

    $compStatusMap = [
        'operational'  => ['label' => 'Operacional',  'dot' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
        'degraded'     => ['label' => 'Degradado',     'dot' => 'bg-amber-500',   'text' => 'text-amber-700'],
        'outage'       => ['label' => 'Indisponível',  'dot' => 'bg-red-500',     'text' => 'text-red-700'],
        'maintenance'  => ['label' => 'Manutenção',    'dot' => 'bg-indigo-500',  'text' => 'text-indigo-700'],
    ];

    $allOperational = collect($components)->every(fn($comp) => $comp['status'] === 'operational');
@endphp

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    {{-- Header --}}
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-2xl px-4 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0 overflow-hidden bg-slate-900 p-1.5">
                    <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="{{ $service_name }}" class="w-full h-full object-contain rounded">
                </div>
                <div>
                    <h1 class="text-sm font-semibold text-slate-900 leading-tight">{{ $service_name }}</h1>
                    <p class="text-xs text-slate-500">Status dos serviços</p>
                </div>
            </div>
            @if($frontUrl)
            <div class="flex items-center gap-2">
                <a href="{{ $frontUrl }}" class="text-xs font-medium text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50 transition-colors">
                    Voltar ao site
                </a>
                <a href="{{ $frontUrl }}/autenticacao" class="text-xs font-medium text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50 transition-colors">
                    Entrar
                </a>
            </div>
            @endif
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-2xl px-4 py-8 space-y-6">

            {{-- Banner principal --}}
            <div class="slide-up rounded-2xl bg-gradient-to-r {{ $c['banner'] }} p-6 text-white shadow-lg shadow-{{ $s['color'] }}-500/10">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3.5 w-3.5">
                        <span class="pulse-dot absolute inline-flex h-full w-full rounded-full {{ $c['dot'] }} opacity-75"></span>
                        <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-white"></span>
                    </span>
                    <h2 class="text-lg font-semibold">{{ $s['label'] }}</h2>
                </div>

                @if($severity !== 'none')
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs font-medium text-white/70">Criticidade:</span>
                        <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-semibold text-white backdrop-blur-sm">
                            {{ $sev['label'] }}
                        </span>
                    </div>
                @endif

                @if(!empty($updated_at))
                    <p class="mt-3 text-xs text-white/60">
                        Atualizado {{ \Carbon\Carbon::parse($updated_at)->locale('pt_BR')->diffForHumans() }}
                        · {{ \Carbon\Carbon::parse($updated_at)->locale('pt_BR')->format('d/m/Y \à\s H:i') }}
                    </p>
                @endif
            </div>

            {{-- Mensagem do operador --}}
            @if(!empty($message))
                <div class="slide-up slide-up-d1 rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} px-5 py-4">
                    <div class="flex gap-3">
                        <div class="mt-0.5 shrink-0">
                            <svg class="h-5 w-5 {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium {{ $c['text'] }}">Comunicado</p>
                            <p class="mt-1 text-sm text-slate-700 leading-relaxed">{{ $message }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Componentes --}}
            <div class="slide-up slide-up-d2 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900">Componentes do serviço</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($components as $comp)
                        @php $cs = $compStatusMap[$comp['status']] ?? $compStatusMap['operational']; @endphp
                        <div class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full {{ $cs['dot'] }}"></span>
                                <span class="text-sm text-slate-700">{{ $comp['label'] }}</span>
                            </div>
                            <span class="text-xs font-medium {{ $cs['text'] }}">{{ $cs['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Criticidade (se não for none) --}}
            @if($severity !== 'none')
                <div class="slide-up slide-up-d3 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Nível de criticidade</h3>
                    </div>
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-4">
                            @php
                                $levels = ['low', 'medium', 'high', 'critical'];
                                $activeIdx = array_search($severity, $levels);
                            @endphp
                            <div class="flex-1 flex gap-1.5">
                                @foreach($levels as $i => $lvl)
                                    @php
                                        $lvlColors = [
                                            'low'      => 'bg-sky-400',
                                            'medium'   => 'bg-amber-400',
                                            'high'     => 'bg-orange-500',
                                            'critical' => 'bg-red-500',
                                        ];
                                        $isActive = $activeIdx !== false && $i <= $activeIdx;
                                    @endphp
                                    <div class="flex-1 h-2.5 rounded-full {{ $isActive ? $lvlColors[$lvl] : 'bg-slate-200' }} transition-colors"></div>
                                @endforeach
                            </div>
                            <span class="inline-flex items-center rounded-full {{ $sev['badge'] }} px-2.5 py-1 text-xs font-semibold">
                                {{ $sev['label'] }}
                            </span>
                        </div>
                        <div class="mt-3 flex justify-between text-[10px] text-slate-400 font-medium uppercase tracking-wider">
                            <span>Baixa</span>
                            <span>Média</span>
                            <span>Alta</span>
                            <span>Crítica</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Informações gerais --}}
            <div class="slide-up slide-up-d4 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900">Informações</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-slate-500">Serviço</span>
                        <span class="text-sm font-medium text-slate-900">{{ $service_name }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-slate-500">Status atual</span>
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium {{ $c['text'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $c['dot'] }}"></span>
                            {{ $s['short'] }}
                        </span>
                    </div>
                    @if($severity !== 'none')
                        <div class="flex items-center justify-between px-5 py-3.5">
                            <span class="text-sm text-slate-500">Criticidade</span>
                            <span class="inline-flex items-center rounded-full {{ $sev['badge'] }} px-2 py-0.5 text-xs font-semibold">{{ $sev['label'] }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-slate-500">Componentes operacionais</span>
                        <span class="text-sm font-medium text-slate-900">
                            {{ collect($components)->where('status', 'operational')->count() }} / {{ count($components) }}
                        </span>
                    </div>
                    @if(!empty($updated_at))
                        <div class="flex items-center justify-between px-5 py-3.5">
                            <span class="text-sm text-slate-500">Última atualização</span>
                            <span class="text-sm text-slate-700">{{ \Carbon\Carbon::parse($updated_at)->locale('pt_BR')->format('d/m/Y H:i:s') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-2xl px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
            <span>&copy; {{ date('Y') }} {{ $service_name }}. Todos os direitos reservados.</span>
            @if($frontUrl ?? null)
            <div class="flex items-center gap-4">
                <a href="{{ $frontUrl }}" class="hover:text-slate-600 transition-colors">Página inicial</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $frontUrl }}/autenticacao" class="hover:text-slate-600 transition-colors">Entrar</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $frontUrl }}/privacidade" class="hover:text-slate-600 transition-colors">Privacidade</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $frontUrl }}/termos-de-uso" class="hover:text-slate-600 transition-colors">Termos</a>
            </div>
            @endif
        </div>
    </footer>

</body>
</html>
