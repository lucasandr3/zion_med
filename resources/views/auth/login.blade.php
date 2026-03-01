<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar — Zion Med</title>
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

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            height: 100%;
            overflow: hidden;
        }
        body {
            height: 100%;
            min-height: 100vh;
            overflow: hidden;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background-color: var(--c-bg);
            color: var(--c-text);
            transition: background-color 0.25s, color 0.25s;
        }

        /* ── Variáveis locais por modo ── */
        :root {
            --login-card-bg:     rgba(255, 255, 255, 0.6);
            --login-card-border: rgba(0, 0, 0, 0.07);
        }
        .dark {
            --login-card-bg:     rgba(17, 17, 24, 0.65);
            --login-card-border: rgba(255, 255, 255, 0.05);
        }

        /* ── Toggle dark mode ── */
        #login-dark-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--c-border);
            background: var(--c-surface);
            color: var(--c-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            z-index: 50;
        }
        #login-dark-btn:hover { background: var(--c-soft); color: var(--c-text); }

        /* ─────────────────────────────────
           PÁGINA: centralizada, sem scroll
        ───────────────────────────────── */
        .login-page {
            height: 100vh;
            min-height: 100vh;
            max-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Gradiente de fundo */
        .login-bg-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--c-soft), var(--c-bg));
            z-index: 0;
        }

        /* Blob decorativo — canto superior direito */
        .login-blob-tr {
            position: absolute;
            top: -10%;
            right: -10%;
            width: 50%;
            height: 50%;
            background: var(--c-focus);
            filter: blur(120px);
            border-radius: 50%;
            z-index: 0;
        }

        /* Blob decorativo — canto inferior esquerdo */
        .login-blob-bl {
            position: absolute;
            bottom: -10%;
            left: -10%;
            width: 50%;
            height: 50%;
            background: var(--c-focus);
            filter: blur(100px);
            border-radius: 50%;
            z-index: 0;
        }

        /* ── Wrapper principal ── */
        .login-wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 2.5rem;
            padding: 2rem 3rem;
            max-height: 100%;
            overflow: hidden;
        }

        /* ─────────────────────────────────
           ESQUERDA — branding
        ───────────────────────────────── */
        .login-brand {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
            min-width: 0;
        }

        .login-brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .login-brand-logo-icon {
            width: 38px;
            height: 38px;
            background: var(--c-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .login-brand-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }
        .login-brand-logo-name {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--c-text);
        }

        .login-brand-text {
            margin-top: 0.5rem;
        }
        .login-brand-text h1 {
            font-size: clamp(1.75rem, 3.5vw, 2.75rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em;
            color: var(--c-text);
            margin-bottom: 1rem;
        }
        .login-brand-text h1 .hl {
            color: var(--c-primary);
        }
        .login-brand-text p {
            font-size: 0.9375rem;
            line-height: 1.7;
            color: var(--c-muted);
            max-width: 380px;
            font-weight: 400;
        }

        .login-brand-footer {
            font-size: 0.75rem;
            color: var(--c-muted);
            font-weight: 500;
            opacity: 0.75;
        }

        /* ─────────────────────────────────
           DIREITA — card compacto, cabe na tela
        ───────────────────────────────── */
        .login-card {
            flex: 0 0 400px;
            width: 400px;
            max-width: 100%;
        }

        .login-card-inner {
            background: var(--login-card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--login-card-border);
            border-radius: 24px;
            padding: 1.75rem 1.5rem 2rem;
            /* box-shadow: 0 25px 60px rgba(0,0,0,0.12), 0 4px 16px rgba(0,0,0,0.06); */
            position: relative;
            overflow: hidden;
        }

        .login-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--c-text);
            margin-bottom: 0.25rem;
        }
        .login-card-subtitle {
            font-size: 0.8125rem;
            color: var(--c-muted);
            margin-bottom: 1.25rem;
        }

        /* ── Form e campos ── */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        .login-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }

        .login-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2px;
        }
        .login-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--c-text);
        }
        .login-forgot {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--c-primary);
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .login-forgot:hover { opacity: 0.75; }

        .login-input-wrap {
            position: relative;
            display: flex;
            align-items: stretch;
            width: 100%;
        }
        .login-input-icon-l {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-muted);
            pointer-events: none;
            font-size: 18px;
            transition: color 0.15s;
        }
        .login-input-wrap:focus-within .login-input-icon-l {
            color: var(--c-primary);
        }
        .login-input-icon-r {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-muted);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.15s;
        }
        .login-input-icon-r:hover { color: var(--c-primary); }

        /* Mesma altura e estilo do .form-input do sistema */
        .login-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.75rem;
            border-radius: 0.5rem;
            border: 1px solid var(--c-border);
            background-color: var(--c-surface);
            color: var(--c-text);
            font-size: 0.875rem;
            line-height: 1.5;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background-color 0.25s;
            box-sizing: border-box;
        }
        .login-input::placeholder { color: var(--c-muted); opacity: 0.7; }
        .login-input:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px var(--c-focus);
        }
        .login-input.pr { padding-right: 2.75rem; }

        .login-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 0;
            margin-top: 0;
            cursor: pointer;
        }
        .login-remember span {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--c-muted);
            user-select: none;
            transition: color 0.15s;
        }
        .login-remember:hover span { color: var(--c-text); }

        .login-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--c-primary);
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 700;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: opacity 0.2s, transform 0.1s;
        }
        .login-btn:hover { opacity: 0.9; }
        .login-btn:active { transform: scale(0.98); }
        .login-btn .btn-arrow {
            font-size: 18px;
            transition: transform 0.2s;
        }
        .login-btn:hover .btn-arrow { transform: translateX(4px); }

        .login-register {
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--c-muted);
            padding-top: 0.5rem;
        }
        .login-register a {
            color: var(--c-primary);
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
            transition: opacity 0.15s;
        }
        .login-register a:hover { opacity: 0.75; }

        /* Erro */
        .login-error {
            border-radius: 12px;
            border: 1px solid rgba(239,68,68,0.25);
            background: rgba(239,68,68,0.08);
            padding: 0.875rem 1rem;
            font-size: 0.8rem;
            color: #ef4444;
        }

        @media (max-width: 900px) {
            .login-brand { display: none; }
            .login-wrap {
                justify-content: center;
                padding: 1.5rem 1.25rem;
            }
            .login-card { flex: none; width: 100%; max-width: 380px; }
            .login-card-inner { padding: 1.5rem 1.25rem 1.75rem; border-radius: 20px; }
        }
    </style>
</head>
<body class="{{ $themeBodyClasses ?? 'theme-zion-blue' }}">

    {{-- Anti-FOUC --}}
    <script>(function(){try{if(localStorage.getItem('zionmed_dark_mode')==='1')document.body.classList.add('dark');}catch(e){}}());</script>

    {{-- Toggle dark mode --}}
    <button type="button" id="login-dark-btn" data-tooltip="Alternar modo escuro" aria-label="Alternar modo escuro">
        <span class="material-symbols-outlined" id="login-dark-icon" style="font-size:17px">dark_mode</span>
    </button>

    <div class="login-page">

        {{-- Fundo decorativo --}}
        <div class="login-bg-gradient"></div>
        <div class="login-blob-tr"></div>
        <div class="login-blob-bl"></div>

        <div class="login-wrap">

            {{-- ── ESQUERDA: branding ── --}}
            <div class="login-brand">

                <div class="login-brand-logo">
                    <div class="login-brand-logo-icon">
                        <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med">
                    </div>
                    <span class="login-brand-logo-name">Zion Med</span>
                </div>

                <div class="login-brand-text">
                    <h1>
                        Cuidando da saúde<br>
                        <span class="hl">com inteligência.</span>
                    </h1>
                    <p>
                        A plataforma líder para gestão de clínicas e protocolos que impulsionam o crescimento e a eficiência operacional.
                    </p>
                </div>

                <p class="login-brand-footer">© {{ date('Y') }} Zion Med. Todos os direitos reservados.</p>

            </div>

            {{-- ── DIREITA: card ── --}}
            <div class="login-card">
                <div class="login-card-inner">

                    <h2 class="login-card-title">Bem-vindo de volta</h2>
                    <p class="login-card-subtitle">Entre na sua conta para continuar sua análise.</p>

                    @if($errors->any())
                        <div class="login-error" style="margin-bottom:1.5rem">
                            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="login-form" id="login-form">
                        @csrf

                        {{-- Usuário --}}
                        <div class="login-field">
                            <div class="login-label-row">
                                <label class="login-label" for="login-email">Usuário</label>
                            </div>
                            <div class="login-input-wrap">
                                <span class="material-symbols-outlined login-input-icon-l">person</span>
                                <input type="email" name="email" id="login-email"
                                       value="{{ old('email') }}" required autofocus
                                       class="login-input" placeholder="Seu nome de usuário">
                            </div>
                        </div>

                        {{-- Senha --}}
                        <div class="login-field">
                            <div class="login-label-row">
                                <label class="login-label" for="login-password">Sua Senha</label>
                                <a href="#" class="login-forgot">Esqueceu a senha?</a>
                            </div>
                            <div class="login-input-wrap">
                                <span class="material-symbols-outlined login-input-icon-l">lock</span>
                                <input type="password" name="password" id="login-password" required
                                       class="login-input pr" placeholder="••••••••">
                                <span class="material-symbols-outlined login-input-icon-r"
                                      id="toggle-pw" data-tooltip="Mostrar/ocultar senha" aria-label="Mostrar ou ocultar senha">visibility</span>
                            </div>
                        </div>

                        {{-- Lembrar --}}
                        <label class="login-remember">
                            <input type="checkbox" name="remember">
                            <span>Lembrar neste dispositivo</span>
                        </label>

                        {{-- Botão --}}
                        <button type="submit" class="login-btn">
                            <span>Acessar</span>
                            <span class="material-symbols-outlined btn-arrow">arrow_forward</span>
                        </button>

                        {{-- Solicitar acesso --}}
                        <p class="login-register">
                            Ainda não tem conta? <a href="#">Solicitar acesso</a>
                        </p>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
    (function(){
        var btn     = document.getElementById('login-dark-btn');
        var icon    = document.getElementById('login-dark-icon');
        var pwInput = document.getElementById('login-password');
        var pwBtn   = document.getElementById('toggle-pw');
        var form    = document.getElementById('login-form');
        var emailIn = document.getElementById('login-email');

        function syncIcon(){
            icon.textContent = document.body.classList.contains('dark') ? 'light_mode' : 'dark_mode';
        }
        syncIcon();

        btn.addEventListener('click', function(){
            document.body.classList.toggle('dark');
            try{ localStorage.setItem('zionmed_dark_mode', document.body.classList.contains('dark') ? '1' : '0'); }catch(e){}
            syncIcon();
        });

        pwBtn.addEventListener('click', function(){
            var t = pwInput.getAttribute('type');
            pwInput.setAttribute('type', t === 'password' ? 'text' : 'password');
            pwBtn.textContent = t === 'password' ? 'visibility_off' : 'visibility';
        });

        try{
            var saved = localStorage.getItem('zionmed_login_email');
            if(saved && !emailIn.value) emailIn.value = saved;
        }catch(e){}

        form.addEventListener('submit', function(){
            try{ if(emailIn.value) localStorage.setItem('zionmed_login_email', emailIn.value); }catch(e){}
        });
    })();
    </script>
</body>
</html>
