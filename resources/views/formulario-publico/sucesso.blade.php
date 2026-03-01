<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulário enviado - Zion Med</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="ZionMed" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Jost', sans-serif; -webkit-font-smoothing: antialiased; }
        h1 { font-family: 'Cormorant Garamond', serif; }
        body { background: #f7f5f2; min-height: 100vh; }
        body.dark { background: #0f0f14; }

        .logo-wrap {
            width: 48px; height: 48px; border-radius: 12px; background: #1a1a2e;
            display: flex; align-items: center; justify-content: center; overflow: hidden; border: none !important; box-shadow: none;
        }
        .logo-wrap:has(img) { background: transparent; width: auto; height: auto; min-width: 0; min-height: 0; }
        .logo-wrap img { width: auto; height: auto; max-width: 48px; max-height: 48px; border: none !important; outline: none; box-shadow: none; display: block; }
        body.dark .logo-wrap { background: #2a2a3e; }

        .bio-text { color: #1a1a2e; }
        body.dark .bio-text { color: #e8e8f0; }
        .bio-muted { color: #9e9b96; }
        body.dark .bio-muted { color: #8a8a96; }
        .bio-border { border-color: #e8e4de; }
        body.dark .bio-border { border-color: #2a2a3e; }
        .bio-bg { background: #fff; }
        body.dark .bio-bg { background: #16161e; }
        .bio-bg-soft { background: #f0ede8; }
        body.dark .bio-bg-soft { background: #1a1a24; }

        .bio-header-actions button {
            width: 36px; height: 36px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: opacity 0.2s;
        }
        .bio-header-actions button:hover { opacity: 0.85; }
        .bio-header-actions .btn-theme { background: #f0ede8; color: #5a5650; }
        body.dark .bio-header-actions .btn-theme { background: #1e1e2a; color: #8a8a96; }
        .success-icon-wrap { background: #e8f5e9; }
        .success-icon-wrap .material-symbols-outlined { color: #2e7d32; }
        body.dark .success-icon-wrap { background: rgba(76,175,80,0.2); }
        body.dark .success-icon-wrap .material-symbols-outlined { color: #81c784; }
    </style>
</head>
<body class="formulario-publico-page">

    {{-- Dark mode independente: zionmed_form_dark_mode (não afeta admin nem Link Bio) --}}
    <script>(function(){try{var d=localStorage.getItem('zionmed_form_dark_mode');if(d==='1')document.body.classList.add('dark');}catch(e){}}());</script>

    <div class="fixed top-0 left-0 right-0 z-20 flex justify-end px-4 pt-4">
        <div class="bio-header-actions flex items-center justify-end gap-2">
            <button type="button" onclick="var b=document.body;b.classList.toggle('dark');try{localStorage.setItem('zionmed_form_dark_mode',b.classList.contains('dark')?'1':'0')}catch(e){}" class="btn-theme" data-tooltip="Alternar tema" aria-label="Alternar tema">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center pt-16 pb-12 px-4">
        <div class="w-full max-w-md">
            <div class="bio-bg rounded-2xl border bio-border p-8 text-center" style="border-width:1px">
                <div class="success-icon-wrap w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-5">
                    <span class="material-symbols-outlined" style="font-size:32px">check_circle</span>
                </div>
                <h1 class="text-2xl font-light bio-text mb-2">Formulário enviado!</h1>
                @if($clinic)
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <div class="logo-wrap shrink-0">
                            @if($clinic->logo_path)
                                <img src="{{ asset('storage/'.$clinic->logo_path) }}" alt="{{ $clinic->name }}">
                            @else
                                <span class="text-xl font-medium text-[#f7f5f2]" style="font-family:'Cormorant Garamond',serif">{{ mb_strtoupper(mb_substr($clinic->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <p class="bio-text font-medium">{{ $clinic->name }}</p>
                    </div>
                    <p class="bio-muted mb-5">Recebemos seu formulário. Obrigado!</p>
                @else
                    <p class="bio-muted mb-5">Seu protocolo foi registrado com sucesso.</p>
                @endif
                @if($protocol_number)
                    <div class="bio-bg-soft border bio-border rounded-xl py-4 px-5" style="border-width:1px">
                        <p class="text-xs bio-muted uppercase tracking-wider mb-1 font-medium">Protocolo</p>
                        <p class="text-xl font-mono font-semibold bio-text">{{ $protocol_number }}</p>
                    </div>
                    <p class="text-xs bio-muted mt-3 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined" style="font-size:14px">info</span>
                        Guarde este número para acompanhamento.
                    </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
