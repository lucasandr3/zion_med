<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Zion Med') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="ZionMed" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Botões ghost/default: fundo e cor do tema (--c-soft, --c-primary) --}}
    <style>
    .btn-ghost, .btn-default-bg {
        background-color: var(--c-soft) !important;
        border: 1px solid var(--c-border) !important;
        color: var(--c-primary) !important;
        text-decoration: none !important;
    }
    .btn-ghost:hover, .btn-default-bg:hover {
        background-color: color-mix(in srgb, var(--c-primary) 12%, var(--c-soft)) !important;
        border-color: var(--c-primary) !important;
        color: var(--c-primary) !important;
    }
    </style>
    @stack('styles')
</head>
<body class="{{ $themeBodyClasses ?? 'theme-ocean-blue' }} min-h-screen"
      style="background-color:var(--c-bg);color:var(--c-text)">

    {{-- Anti-FOUC --}}
    <script>
    (function(){
        try{
            if(localStorage.getItem('zionmed_dark_mode')==='1')  document.body.classList.add('dark');
            if(localStorage.getItem('zionmed_sidebar_collapsed')==='1') document.body.classList.add('sidebar-collapsed');
        }catch(e){}
    })();
    </script>

    {{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 flex flex-col
                  -translate-x-full lg:translate-x-0">

        {{-- Cabeçalho da sidebar: só a marca (sem botão de collapse) --}}
        <div class="h-14 flex items-center px-3.5 shrink-0"
             style="border-bottom:1px solid var(--c-border)">
            {{-- Ícone sempre visível, texto some quando encolhido --}}
            <div class="sidebar-brand flex items-center gap-2.5 min-w-0 w-full">
                <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--c-primary)">
                    <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med" class="w-full h-full rounded-lg object-contain" style="padding:2px">
                </div>
                <span class="sidebar-label font-semibold text-sm truncate" style="color:var(--c-text)">Zion Med</span>
            </div>
        </div>

        {{-- Empresa atual (abaixo da logo, acima do menu) --}}
        @if(!empty($currentClinic))
        <div class="sidebar-clinic px-2 py-2 shrink-0" style="border-bottom:1px solid var(--c-border)">
            <div class="flex items-center gap-2.5 min-w-0">
                @if($currentClinic->logo_path)
                    <img src="{{ asset('storage/'.$currentClinic->logo_path) }}" alt="" class="w-9 h-9 rounded-lg object-contain shrink-0 border flex-shrink-0" style="border-color:var(--c-border);background:var(--c-surface);padding:2px">
                @else
                    <div class="w-9 h-9 rounded-lg shrink-0 flex items-center justify-center text-sm font-bold flex-shrink-0" style="background:var(--c-soft);color:var(--c-primary)">
                        {{ mb_strtoupper(mb_substr($currentClinic->name, 0, 1)) }}
                    </div>
                @endif
                <div class="sidebar-label min-w-0 flex-1">
                    <p class="text-xs font-semibold truncate" style="color:var(--c-text)">{{ $currentClinic->name }}</p>
                    @if($currentClinic->address)
                        <p class="text-[0.65rem] truncate mt-0.5" style="color:var(--c-muted)">{{ $currentClinic->address }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Navegação --}}
        <nav class="flex-1 px-2 py-3 overflow-y-auto space-y-0.5">
            <p class="sidebar-section-label mt-1 mb-2">MENU</p>

            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               data-tooltip="Dashboard">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">dashboard</span>
                <span class="sidebar-label">Dashboard</span>
            </a>

            @can('manage-templates')
            <a href="{{ route('templates.index') }}"
               class="nav-link {{ request()->routeIs('templates.*') ? 'active' : '' }}"
               data-tooltip="Templates">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">description</span>
                <span class="sidebar-label">Templates</span>
            </a>
            @endcan

            @if(auth()->user()->can('manage-templates') || auth()->user()->can('view-submissions'))
            <a href="{{ route('links-publicos.index') }}"
               class="nav-link {{ request()->routeIs('links-publicos.*') ? 'active' : '' }}"
               data-tooltip="Links para enviar">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">link</span>
                <span class="sidebar-label">Links para enviar</span>
            </a>
            @endif

            @can('view-submissions')
            <a href="{{ route('protocolos.index') }}"
               class="nav-link {{ request()->routeIs('protocolos.*') ? 'active' : '' }}"
               data-tooltip="Protocolos">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">inbox</span>
                <span class="sidebar-label">Protocolos</span>
            </a>
            @endcan

            <a href="{{ route('notificacoes.index') }}"
               class="nav-link {{ request()->routeIs('notificacoes.*') ? 'active' : '' }}"
               data-tooltip="Notificações"
               style="position:relative">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">notifications</span>
                <span class="sidebar-label">Notificações</span>
                @if(($unreadNotifications ?? 0) > 0)
                    <span class="sidebar-label" style="margin-left:auto;min-width:18px;height:18px;border-radius:9px;background:var(--c-primary);color:#fff;font-size:0.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px">
                        {{ ($unreadNotifications ?? 0) > 99 ? '99+' : ($unreadNotifications ?? 0) }}
                    </span>
                @endif
            </a>

            @if(auth()->user()->can('manage-clinic') || auth()->user()->can('manage-users'))
                <p class="sidebar-section-label mt-5 mb-2">ADMINISTRAÇÃO</p>
            @endif

            @can('manage-clinic')
            <a href="{{ route('clinica.configuracoes.edit') }}"
               class="nav-link {{ request()->routeIs('clinica.configuracoes.*') ? 'active' : '' }}"
               data-tooltip="Empresa">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">business</span>
                <span class="sidebar-label">Empresa</span>
            </a>
            <a href="{{ route('link-bio.index') }}"
               class="nav-link {{ request()->routeIs('link-bio.*') ? 'active' : '' }}"
               data-tooltip="Link Bio">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">link</span>
                <span class="sidebar-label">Link Bio</span>
            </a>
            <a href="{{ route('clinica.integracoes.index') }}"
               class="nav-link {{ request()->routeIs('clinica.integracoes.*') ? 'active' : '' }}"
               data-tooltip="Integrações">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">api</span>
                <span class="sidebar-label">Integrações</span>
            </a>
            @endcan

            @can('manage-users')
            <a href="{{ route('usuarios.index') }}"
               class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
               data-tooltip="Usuários">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">group</span>
                <span class="sidebar-label">Usuários</span>
            </a>
            @endcan
        </nav>

        {{-- Rodapé: usuário --}}
        <div id="sidebar-footer" class="shrink-0 px-2 py-2" style="border-top:1px solid var(--c-border)">
            <div class="relative">
                <button id="user-menu-btn"
                        class="flex items-center gap-2.5 w-full px-2 py-2 rounded-lg text-left transition-colors"
                        onmouseover="this.style.background='var(--c-soft)'"
                        onmouseout="this.style.background='transparent'">
                    <div class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center text-xs font-bold"
                         style="background:var(--c-primary);color:#fff">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="sidebar-label flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate" style="color:var(--c-text)">{{ auth()->user()->name }}</p>
                        <p class="truncate" style="color:var(--c-muted);font-size:0.7rem">{{ auth()->user()->email }}</p>
                    </div>
                    <span class="material-symbols-outlined sidebar-label shrink-0" style="font-size:15px;color:var(--c-muted)">unfold_more</span>
                </button>

                <div id="user-dropdown" class="user-dropdown hidden">
                    @if(!empty($showTrocarEmpresa))
                    <a href="{{ route('clinica.escolher') }}">
                        <span class="material-symbols-outlined" style="font-size:16px">swap_horiz</span>
                        Trocar empresa
                    </a>
                    @endif
                    @if(auth()->user()->isPlatformAdmin())
                    <a href="{{ route('platform.dashboard') }}">
                        <span class="material-symbols-outlined" style="font-size:16px">admin_panel_settings</span>
                        Admin da plataforma
                    </a>
                    @endif
                    @can('manage-clinic')
                    <a href="{{ route('clinica.configuracoes.edit') }}">
                        <span class="material-symbols-outlined" style="font-size:16px">settings</span>
                        Configurações
                    </a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <span class="material-symbols-outlined" style="font-size:16px">logout</span>
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- ─── Overlay mobile ──────────────────────────────────────────────── --}}
    <div id="sidebar-overlay"
         class="fixed inset-0 z-20 hidden lg:hidden"
         style="background:rgba(0,0,0,0.55);backdrop-filter:blur(4px)"></div>

    {{-- ─── Área principal ──────────────────────────────────────────────── --}}
    <div id="main-wrapper" class="flex flex-col min-h-screen">

        @php
            $allThemes       = app(\App\Services\ThemeService::class)->getAvailableThemes();
            $currentThemeKey = str_replace('theme-', '', explode(' ', $themeBodyClasses ?? 'theme-ocean-blue')[0]);
        @endphp

        {{-- Top header --}}
        <header id="top-header"
                class="h-14 flex items-center justify-between px-3 lg:px-5 sticky top-0 z-10 shrink-0">
            {{-- Esquerda: voltar (quando definido) + menu/colapso + título --}}
            <div class="flex items-center gap-3">
                @hasSection('header_back_url')
                <a href="{{ trim(view()->yieldContent('header_back_url')) }}"
                   class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors shrink-0"
                   style="color:var(--c-muted)"
                   data-tooltip="{{ trim(view()->yieldContent('header_back_label')) ?: 'Voltar' }}"
                   aria-label="{{ trim(view()->yieldContent('header_back_label')) ?: 'Voltar' }}"
                   onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                   onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:21px">arrow_back</span>
                </a>
                @endif
                {{--
                    Um único botão:
                    - Mobile: abre/fecha a sidebar (overlay)
                    - Desktop: colapsa/expande a sidebar
                --}}
                <button id="sidebar-toggle-btn"
                        type="button"
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors"
                        style="color:var(--c-muted)"
                        data-tooltip="Menu"
                        aria-label="Abrir ou fechar menu"
                        onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:21px">menu</span>
                </button>

                {{-- Título da página --}}
                <h2 class="font-semibold" style="font-size:0.9375rem;color:var(--c-text)">
                    @yield('title', 'Zion Med')
                </h2>
            </div>

            {{-- Direita: Theme picker + Dark toggle --}}
            <div style="display:flex;align-items:center;gap:0.5rem">

                {{-- Theme picker --}}
                <div style="position:relative" id="theme-picker-wrapper">
                    <button id="theme-picker-btn"
                            style="display:flex;align-items:center;gap:7px;height:32px;padding:0 10px;border-radius:8px;font-size:0.75rem;font-weight:600;color:var(--c-muted);border:1px solid var(--c-border);background:transparent;cursor:pointer;transition:all 0.15s"
                            onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)';this.style.borderColor='var(--c-muted)'"
                            onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)';this.style.borderColor='var(--c-border)'">
                        <span id="theme-dot" style="width:10px;height:10px;border-radius:50%;background:var(--c-primary);flex-shrink:0;display:inline-block"></span>
                        Tema
                        <span class="material-symbols-outlined" id="theme-chevron" style="font-size:15px;transition:transform 0.2s">expand_more</span>
                    </button>

                    <div id="theme-dropdown" class="hidden">
                        <p style="font-size:0.625rem;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--c-muted);padding:0 0.375rem;margin-bottom:0.5rem">
                            Selecione um tema
                        </p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px">
                            @foreach($allThemes as $key => $meta)
                            <button class="theme-option {{ $key === $currentThemeKey ? 'active' : '' }}"
                                    data-theme="{{ $key }}"
                                    data-primary="{{ $meta['primary'] }}"
                                    data-route="{{ route('clinica.configuracoes.update') }}">
                                <span style="width:11px;height:11px;border-radius:50%;background:{{ $meta['primary'] }};flex-shrink:0;display:inline-block"></span>
                                {{ $meta['label'] }}
                                @if($key === $currentThemeKey)
                                    <span class="material-symbols-outlined chk-icon" style="font-size:13px;margin-left:auto;color:var(--c-primary)">check</span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Dark mode toggle --}}
                <button id="dark-mode-toggle"
                        type="button"
                        style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--c-border);background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s"
                        data-tooltip="Alternar modo escuro"
                        aria-label="Alternar modo escuro"
                        data-route="{{ route('clinica.configuracoes.update') }}"
                        onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" id="dark-mode-icon" style="font-size:19px">
                        {{ ($isDarkMode ?? false) ? 'light_mode' : 'dark_mode' }}
                    </span>
                </button>

                {{-- Separador --}}
                <div style="width:1px;height:20px;background:var(--c-border);margin:0 2px"></div>

                {{-- Notificações --}}
                <a href="{{ route('notificacoes.index') }}"
                   id="notif-btn"
                   style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--c-border);background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s;text-decoration:none"
                   data-tooltip="Notificações"
                   aria-label="Notificações"
                   onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                   onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:19px">notifications</span>
                    @if(($unreadNotifications ?? 0) > 0)
                        <span style="position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;border-radius:8px;background:var(--c-primary);border:2px solid var(--c-bg);display:flex;align-items:center;justify-content:center;padding:0 3px;box-sizing:border-box">
                            <span style="font-size:0.55rem;font-weight:700;color:#fff;line-height:1">
                                {{ ($unreadNotifications ?? 0) > 99 ? '99+' : ($unreadNotifications ?? 0) }}
                            </span>
                        </span>
                    @endif
                </a>

                {{-- Sair --}}
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit"
                            style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--c-border);background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s"
                            data-tooltip="Sair"
                            aria-label="Sair"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.borderColor='rgba(239,68,68,0.35)';this.style.color='#ef4444'"
                            onmouseout="this.style.background='transparent';this.style.borderColor='var(--c-border)';this.style.color='var(--c-muted)'">
                        <span class="material-symbols-outlined" style="font-size:19px">logout</span>
                    </button>
                </form>
            </div>
        </header>

        {{-- Conteúdo --}}
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            @unless(request()->routeIs('clinica.configuracoes.edit'))
                @if(session('success'))
                    <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
                @endif
                @if(session('error'))
                    <x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>
                @endif
                @if(session('billing_warning'))
                    <x-ui.alert type="warning" class="mb-5">{{ session('billing_warning') }}</x-ui.alert>
                @endif
            @endunless

            @yield('content')
        </main>
    </div>

    {{-- Loader global para requisições (axios/fetch) ──────────────────────── --}}
    <div id="global-loader" class="global-loader" aria-hidden="true">
        <div class="global-loader__backdrop"></div>
        <div class="global-loader__spinner" role="status" aria-label="Carregando">
            <span class="global-loader__dot"></span>
            <span class="global-loader__dot"></span>
            <span class="global-loader__dot"></span>
        </div>
    </div>
    <style>
    .global-loader {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    .global-loader.is-active {
        pointer-events: auto;
        opacity: 1;
        visibility: visible;
    }
    .global-loader__backdrop {
        position: absolute;
        inset: 0;
        background: var(--c-bg);
        opacity: 0.85;
    }
    .global-loader__spinner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .global-loader__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--c-primary);
        animation: global-loader-bounce 0.6s ease-in-out infinite both;
    }
    .global-loader__dot:nth-child(1) { animation-delay: 0s; }
    .global-loader__dot:nth-child(2) { animation-delay: 0.15s; }
    .global-loader__dot:nth-child(3) { animation-delay: 0.3s; }
    @keyframes global-loader-bounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.6; }
        40% { transform: scale(1); opacity: 1; }
    }
    </style>

    {{-- ─── Scripts ──────────────────────────────────────────────────────── --}}
    <script>
    (function () {
        var body      = document.body;
        var sidebar   = document.getElementById('sidebar');
        var overlay   = document.getElementById('sidebar-overlay');
        var toggleBtn = document.getElementById('sidebar-toggle-btn');

        /* Detecta se é mobile (< 1024px) */
        function isMobile() { return window.innerWidth < 1024; }

        /* Abre sidebar em modo overlay (mobile) */
        function openSidebar(){
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            body.style.overflow = 'hidden';
        }
        function closeSidebar(){
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            body.style.overflow = '';
        }

        if (overlay) overlay.addEventListener('click', closeSidebar);

        /* O botão do header faz coisas diferentes dependendo do tamanho */
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                if (isMobile()) {
                    /* Mobile: abre/fecha overlay */
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                    } else {
                        closeSidebar();
                    }
                } else {
                    /* Desktop: colapsa/expande sidebar */
                    body.classList.toggle('sidebar-collapsed');
                    var c = body.classList.contains('sidebar-collapsed');
                    try { localStorage.setItem('zionmed_sidebar_collapsed', c ? '1' : '0'); } catch(e) {}
                    /* Remove tooltip ativo se existir */
                    hideTooltip();
                }
            });
        }

        /* ── Tooltips via JS (evita clipping do overflow do sidebar) ── */
        var tooltipEl = null;
        var tooltipTimer = null;

        function showTooltip(link) {
            if (!body.classList.contains('sidebar-collapsed')) return;
            var label = link.dataset.tooltip;
            if (!label) return;

            hideTooltip();

            tooltipEl = document.createElement('div');
            tooltipEl.id = 'sidebar-tooltip';
            tooltipEl.textContent = label;
            tooltipEl.style.cssText = [
                'position:fixed',
                'z-index:9999',
                'pointer-events:none',
                'background:var(--c-elevated)',
                'color:var(--c-text)',
                'border:1px solid var(--c-border)',
                'border-radius:6px',
                'padding:5px 10px',
                'font-size:0.75rem',
                'font-weight:500',
                'white-space:nowrap',
                'box-shadow:0 4px 18px rgba(0,0,0,0.25)',
                'opacity:0',
                'transition:opacity 0.12s ease',
            ].join(';');
            document.body.appendChild(tooltipEl);

            var rect = link.getBoundingClientRect();
            var sidebarW = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--sidebar-w')) || 60;
            var tipRect  = tooltipEl.getBoundingClientRect();
            var top = rect.top + rect.height / 2 - tipRect.height / 2;
            var left = rect.right + 10;

            tooltipEl.style.top  = top  + 'px';
            tooltipEl.style.left = left + 'px';

            /* Pequena seta */
            var arrow = document.createElement('div');
            arrow.style.cssText = [
                'position:fixed',
                'z-index:9998',
                'pointer-events:none',
                'width:0',
                'height:0',
                'border-top:5px solid transparent',
                'border-bottom:5px solid transparent',
                'border-right:6px solid var(--c-elevated)',
                'top:' + (rect.top + rect.height / 2 - 5) + 'px',
                'left:' + (rect.right + 4) + 'px',
                'opacity:0',
                'transition:opacity 0.12s ease',
            ].join(';');
            arrow.id = 'sidebar-tooltip-arrow';
            document.body.appendChild(arrow);

            requestAnimationFrame(function() {
                if (tooltipEl)       tooltipEl.style.opacity = '1';
                if (arrow) arrow.style.opacity = '1';
            });
        }

        function hideTooltip() {
            var t = document.getElementById('sidebar-tooltip');
            var a = document.getElementById('sidebar-tooltip-arrow');
            if (t) t.remove();
            if (a) a.remove();
            tooltipEl = null;
        }

        document.querySelectorAll('.nav-link[data-tooltip]').forEach(function(link) {
            link.addEventListener('mouseenter', function() { showTooltip(this); });
            link.addEventListener('mouseleave', hideTooltip);
            link.addEventListener('click', hideTooltip);
        });

        /* ── Dark mode ── */
        var dmBtn = document.getElementById('dark-mode-toggle');

        function syncDark(isDark) {
            var icon = document.getElementById('dark-mode-icon');
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
        }

        function toggleDark() {
            var isDark = body.classList.toggle('dark');
            syncDark(isDark);
            try { localStorage.setItem('zionmed_dark_mode', isDark ? '1' : '0'); } catch(e) {}

            var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrf || !dmBtn) return;
            var fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('_token', csrf);
            fd.append('dark_mode_only', '1');
            fd.append('dark_mode', isDark ? '1' : '0');
            fetch(dmBtn.dataset.route, { method:'POST', body:fd }).catch(function(){});
        }

        syncDark(body.classList.contains('dark'));
        if (dmBtn) dmBtn.addEventListener('click', toggleDark);

        /* ── Theme picker ── */
        var pickerBtn  = document.getElementById('theme-picker-btn');
        var pickerDrop = document.getElementById('theme-dropdown');
        var chevron    = document.getElementById('theme-chevron');

        function closeThemePicker() {
            if (!pickerDrop) return;
            pickerDrop.classList.add('hidden');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        }

        if (pickerBtn && pickerDrop) {
            pickerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var hidden = pickerDrop.classList.toggle('hidden');
                if (chevron) chevron.style.transform = hidden ? 'rotate(0deg)' : 'rotate(180deg)';
            });
        }

        document.querySelectorAll('.theme-option').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var theme   = this.dataset.theme;
                var primary = this.dataset.primary;
                var route   = this.dataset.route;

                var classes = body.className.split(' ');
                body.className = classes.filter(function(c){ return !c.startsWith('theme-'); }).join(' ');
                body.classList.add('theme-' + theme);

                var dot = document.getElementById('theme-dot');
                if (dot) dot.style.background = primary;

                document.querySelectorAll('.theme-option').forEach(function(o){
                    o.classList.remove('active');
                    var chk = o.querySelector('.chk-icon');
                    if (chk) chk.remove();
                });
                this.classList.add('active');
                var checkEl = document.createElement('span');
                checkEl.className = 'material-symbols-outlined chk-icon';
                checkEl.style.cssText = 'font-size:13px;margin-left:auto;color:var(--c-primary)';
                checkEl.textContent = 'check';
                this.appendChild(checkEl);

                closeThemePicker();

                var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrf || !route) return;
                var fd = new FormData();
                fd.append('_method', 'PUT');
                fd.append('_token', csrf);
                fd.append('theme_only', '1');
                fd.append('theme', theme);
                fetch(route, { method:'POST', body:fd }).catch(function(){});
            });
        });

        document.addEventListener('click', function(e) {
            var wrapper = document.getElementById('theme-picker-wrapper');
            if (wrapper && !wrapper.contains(e.target)) closeThemePicker();
        });

        /* ── User dropdown ── */
        var userBtn  = document.getElementById('user-menu-btn');
        var userDrop = document.getElementById('user-dropdown');

        /* Teletransporta o dropdown para o <body> para evitar clipping da sidebar */
        if (userDrop) {
            document.body.appendChild(userDrop);
            userDrop.style.cssText += ';position:fixed;z-index:9990;width:200px;display:none';
        }

        function positionUserDrop() {
            if (!userBtn || !userDrop) return;

            /* Mede altura real do dropdown tornando-o invisível temporariamente */
            userDrop.style.visibility = 'hidden';
            userDrop.style.display    = 'block';
            var dropH = userDrop.offsetHeight;
            var dropW = userDrop.offsetWidth;
            userDrop.style.visibility = '';
            userDrop.style.display    = 'none';

            var rect      = userBtn.getBoundingClientRect();
            var collapsed = body.classList.contains('sidebar-collapsed');
            var vp        = window.innerHeight;
            var margin    = 8;

            if (collapsed) {
                /* Encolhida: abre à direita, alinhado ao topo do botão */
                var top = rect.top;
                /* Se vai sair pela borda inferior, sobe */
                if (top + dropH + margin > vp) top = vp - dropH - margin;
                if (top < margin) top = margin;

                userDrop.style.left   = (rect.right + margin) + 'px';
                userDrop.style.top    = top + 'px';
                userDrop.style.bottom = 'auto';
                userDrop.style.right  = 'auto';
            } else {
                /* Expandida: alinha acima da borda do rodapé da sidebar */
                var footer   = document.getElementById('sidebar-footer');
                var anchorTop = footer ? footer.getBoundingClientRect().top : rect.top;
                var top2     = anchorTop - dropH;
                if (top2 < margin) top2 = rect.bottom + margin;

                userDrop.style.left   = '16px';
                userDrop.style.top    = top2 + 'px';
                userDrop.style.bottom = 'auto';
                userDrop.style.right  = 'auto';
            }
        }

        var userDropOpen = false;

        function openUserDrop() {
            positionUserDrop();
            userDrop.style.display = 'block';
            userDropOpen = true;
        }
        function closeUserDrop() {
            if (userDrop) userDrop.style.display = 'none';
            userDropOpen = false;
        }

        if (userBtn) {
            userBtn.addEventListener('click', function(e){
                e.stopPropagation();
                userDropOpen ? closeUserDrop() : openUserDrop();
            });
        }

        /* Garante que o link "Trocar clínica" sempre navegue (evita bloqueio por filhos com pointer-events:none) */
        var clinicLink = document.getElementById('clinic-switcher-link');
        if (clinicLink) {
            clinicLink.addEventListener('click', function(e) {
                var href = this.getAttribute('href');
                if (href) { e.preventDefault(); window.location.href = href; }
            });
        }

        document.addEventListener('click', function(e){
            if (userDropOpen && userDrop && !userDrop.contains(e.target) && e.target !== userBtn && !userBtn.contains(e.target))
                closeUserDrop();
        });

    })();
    </script>

    @stack('page-scripts')
</body>
</html>
