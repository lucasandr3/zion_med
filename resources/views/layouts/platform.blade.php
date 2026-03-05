<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Zion Med — Plataforma') - {{ config('app.name') }}</title>
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
</head>
<body class="{{ $themeBodyClasses ?? 'theme-ocean-blue' }} min-h-screen"
      style="background-color:var(--c-bg);color:var(--c-text)">

    {{-- Sidebar + estrutura igual ao app --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 flex flex-col
                  -translate-x-full lg:translate-x-0">

        {{-- Cabeçalho da sidebar: marca Zion Med --}}
        <div class="h-14 flex items-center px-3.5 shrink-0"
             style="border-bottom:1px solid var(--c-border)">
            <div class="sidebar-brand flex items-center gap-2.5 min-w-0 w-full">
                <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
                     style="background:var(--c-primary)">
                    <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med"
                         class="w-full h-full rounded-lg object-contain" style="padding:2px">
                </div>
                <span class="sidebar-label font-semibold text-sm truncate" style="color:var(--c-text)">
                    Zion Med — Plataforma
                </span>
            </div>
        </div>

        {{-- Navegação da plataforma --}}
        <nav class="flex-1 px-2 py-3 overflow-y-auto space-y-0.5">
            <p class="sidebar-section-label mt-1 mb-2">MENU</p>

            <a href="{{ route('platform.dashboard') }}"
               class="nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}"
               data-tooltip="Visão geral">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">analytics</span>
                <span class="sidebar-label">Visão geral</span>
            </a>

            <a href="{{ route('platform.tenants.index') }}"
               class="nav-link {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}"
               data-tooltip="Clientes (tenants)">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">apartment</span>
                <span class="sidebar-label">Clientes (tenants)</span>
            </a>

            <a href="{{ route('platform.subscriptions.index') }}"
               class="nav-link {{ request()->routeIs('platform.subscriptions.*') ? 'active' : '' }}"
               data-tooltip="Assinaturas">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">receipt_long</span>
                <span class="sidebar-label">Assinaturas</span>
            </a>

            <a href="{{ route('platform.payments.index') }}"
               class="nav-link {{ request()->routeIs('platform.payments.*') ? 'active' : '' }}"
               data-tooltip="Faturas / cobranças">
                <span class="material-symbols-outlined shrink-0" style="font-size:19px">payments</span>
                <span class="sidebar-label">Faturas / cobranças</span>
            </a>
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

    {{-- Overlay mobile --}}
    <div id="sidebar-overlay"
         class="fixed inset-0 z-20 hidden lg:hidden"
         style="background:rgba(0,0,0,0.55);backdrop-filter:blur(4px)"></div>

    {{-- Área principal --}}
    <div id="main-wrapper" class="flex flex-col min-h-screen">

        {{-- Header superior igual ao app, mas focado em plataforma --}}
        <header id="top-header"
                class="h-14 flex items-center justify-between px-3 lg:px-5 sticky top-0 z-10 shrink-0">
            <div class="flex items-center gap-3">
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

                <h2 class="font-semibold" style="font-size:0.9375rem;color:var(--c-text)">
                    @yield('title', 'Administração da plataforma')
                </h2>
            </div>

            <div class="flex items-center gap-2">

                {{-- Dark mode toggle (somente localStorage, sem salvar em clínica) --}}
                <button id="platform-dark-mode-toggle"
                        type="button"
                        style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--c-border);background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s"
                        data-tooltip="Alternar modo escuro"
                        aria-label="Alternar modo escuro"
                        onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" id="platform-dark-mode-icon" style="font-size:19px">
                        {{ (bool) (request()->user()?->getAttribute('dark_mode') ?? false) ? 'light_mode' : 'dark_mode' }}
                    </span>
                </button>

                {{-- Notificações (usa mesma rota do app) --}}
                <a href="{{ route('notificacoes.index') }}"
                   id="platform-notif-btn"
                   style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--c-border);background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s;text-decoration:none"
                   data-tooltip="Notificações"
                   aria-label="Notificações"
                   onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                   onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:19px">notifications</span>
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
            @if(session('success'))
                <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
            @endif
            @if(session('error'))
                <x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Scripts reutilizando mesmo JS da layout.app (sidebar, dropdown, etc.) --}}
    <script>
    (function () {
        var body      = document.body;
        var sidebar   = document.getElementById('sidebar');
        var overlay   = document.getElementById('sidebar-overlay');
        var toggleBtn = document.getElementById('sidebar-toggle-btn');

        function isMobile() { return window.innerWidth < 1024; }

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

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                if (isMobile()) {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                    } else {
                        closeSidebar();
                    }
                } else {
                    body.classList.toggle('sidebar-collapsed');
                }
            });
        }

        var userBtn  = document.getElementById('user-menu-btn');
        var userDrop = document.getElementById('user-dropdown');

        if (userDrop) {
            document.body.appendChild(userDrop);
            userDrop.style.cssText += ';position:fixed;z-index:9990;width:200px;display:none';
        }

        function positionUserDrop() {
            if (!userBtn || !userDrop) return;

            userDrop.style.visibility = 'hidden';
            userDrop.style.display    = 'block';
            var dropH = userDrop.offsetHeight;
            userDrop.style.visibility = '';
            userDrop.style.display    = 'none';

            var rect      = userBtn.getBoundingClientRect();
            var vp        = window.innerHeight;
            var margin    = 8;
            var top       = rect.top - dropH - margin;
            if (top < margin) top = rect.bottom + margin;

            userDrop.style.left   = '16px';
            userDrop.style.top    = top + 'px';
            userDrop.style.bottom = 'auto';
            userDrop.style.right  = 'auto';
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

        document.addEventListener('click', function(e){
            if (userDropOpen && userDrop && !userDrop.contains(e.target) && e.target !== userBtn && !userBtn.contains(e.target))
                closeUserDrop();
        });

        // Dark mode plataforma
        var dmBtn = document.getElementById('platform-dark-mode-toggle');
        var dmIcon = document.getElementById('platform-dark-mode-icon');

        function syncPlatformDark() {
            var isDark = body.classList.contains('dark');
            if (dmIcon) dmIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
        }

        // aplica preferência salva
        try {
            var saved = localStorage.getItem('zionmed_dark_mode');
            if (saved === '1') body.classList.add('dark');
        } catch (e) {}
        syncPlatformDark();

        if (dmBtn) {
            dmBtn.addEventListener('click', function () {
                var isDark = body.classList.toggle('dark');
                syncPlatformDark();
                try { localStorage.setItem('zionmed_dark_mode', isDark ? '1' : '0'); } catch (e) {}
            });
        }
    })();
    </script>

    @stack('page-scripts')
</body>
</html>
