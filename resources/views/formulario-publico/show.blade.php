<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $template->name }} - Zion Med</title>
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
        h1, h2 { font-family: 'Cormorant Garamond', serif; }
        body { background: #f7f5f2; min-height: 100vh; }
        body.dark { background: #0f0f14; }

        .logo-wrap {
            width: 64px; height: 64px; border-radius: 16px; background: #1a1a2e;
            display: flex; align-items: center; justify-content: center; overflow: hidden; border: none !important; box-shadow: none;
        }
        .logo-wrap:has(img) { background: transparent; width: auto; height: auto; min-width: 0; min-height: 0; }
        .logo-wrap img { width: auto; height: auto; max-width: 64px; max-height: 64px; border: none !important; outline: none; box-shadow: none; display: block; }
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

        .btn-primary {
            background: #1a1a2e; color: #f7f5f2; transition: all 0.2s ease; border: none; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 0.625rem; width: 100%; border-radius: 0.75rem;
            padding: 0.875rem 1rem; font-size: 0.875rem; font-weight: 500; letter-spacing: 0.025em; cursor: pointer;
        }
        body.dark .btn-primary { background: #2a2a3e; color: #e8e8f0; }
        .btn-primary:hover { background: #2c2c4a; }
        body.dark .btn-primary:hover { background: #3a3a52; }

        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #1a1a2e; margin-bottom: 0.375rem; }
        body.dark .form-label { color: #e8e8f0; }
        .form-input, .form-select {
            width: 100%; padding: 0.625rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e8e4de;
            background: #fff; color: #1a1a2e; font-size: 0.875rem; transition: border-color 0.2s;
        }
        body.dark .form-input, body.dark .form-select { background: #16161e; border-color: #2a2a3e; color: #e8e8f0; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #1a1a2e; }
        body.dark .form-input:focus, body.dark .form-select:focus { border-color: #4a4a6a; }

        .bio-header-actions button {
            width: 36px; height: 36px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: opacity 0.2s;
        }
        .bio-header-actions button:hover { opacity: 0.85; }
        .bio-header-actions .btn-theme { background: #f0ede8; color: #5a5650; }
        body.dark .bio-header-actions .btn-theme { background: #1e1e2a; color: #8a8a96; }

        .form-bottom-bar {
            background: #fff;
            border-top: 1px solid #e8e4de;
            padding: 0.75rem 1rem;
            padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
        }
        body.dark .form-bottom-bar { background: #16161e; border-top-color: #2a2a3e; }
        .form-bottom-bar .btn-theme { background: #f0ede8; color: #5a5650; }
        body.dark .form-bottom-bar .btn-theme { background: #1e1e2a; color: #8a8a96; }
        @media (max-width: 767px) {
            .form-card-mobile { background: transparent !important; border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="formulario-publico-page">

    {{-- Dark mode independente: chave zionmed_form_dark_mode (não afeta admin nem Link Bio) --}}
    <script>(function(){try{var d=localStorage.getItem('zionmed_form_dark_mode');if(d==='1')document.body.classList.add('dark');}catch(e){}}());</script>

    {{-- Tema no canto superior direito (apenas desktop) --}}
    <div class="fixed top-0 left-0 right-0 z-20 justify-end px-4 pt-4 hidden md:flex">
        <div class="bio-header-actions flex items-center justify-end gap-2">
            <button type="button" onclick="var b=document.body;b.classList.toggle('dark');try{localStorage.setItem('zionmed_form_dark_mode',b.classList.contains('dark')?'1':'0')}catch(e){}" class="btn-theme" data-tooltip="Alternar tema" aria-label="Alternar tema">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="min-h-screen flex flex-col items-center pt-12 md:pt-16 pb-24 md:pb-12 px-0 md:px-4">
        <div class="w-full md:max-w-lg lg:max-w-xl md:px-0 px-4">
            <div class="form-card-mobile bio-bg md:rounded-2xl border-0 md:border bio-border p-2 md:p-8 md:shadow-none" style="border-width:1px">
                {{-- Header: logo e nome da clínica --}}
                @if($template->clinic)
                <div class="flex items-center gap-3 mb-6 pb-4 bio-border" style="border-bottom-width:1px">
                    <div class="logo-wrap shrink-0">
                        @if($template->clinic->logo_url)
                            <img src="{{ $template->clinic->logo_url }}" alt="{{ $template->clinic->name }}">
                        @else
                            <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med">
                        @endif
                    </div>
                    <div>
                        <h1 class="text-xl font-light bio-text tracking-wide">{{ $template->clinic->name }}</h1>
                        <p class="text-xs bio-muted tracking-wide">Formulário por Zion Med</p>
                    </div>
                </div>
                @endif
                <h2 class="text-lg font-medium bio-text mb-1">{{ $template->name }}</h2>
                @if($template->description)
                    <p class="bio-muted text-sm mb-4">{{ $template->description }}</p>
                @endif
                <p class="text-xs bio-muted mb-6">
                    Seus dados serão utilizados apenas para as finalidades deste formulário e em conformidade com a LGPD.
                    <a href="{{ route('privacidade') }}" target="_blank" rel="noopener" class="underline hover:no-underline bio-text">Política de Privacidade</a>.
                </p>

                <form action="{{ route('formulario-publico.submit', $template->public_token) }}" method="POST" enctype="multipart/form-data" id="public-form" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bio-bg-soft border bio-border" style="border-width:1px">
                        <div>
                            <label for="_submitter_name" class="form-label">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined" style="font-size:15px">person</span>
                                    Seu nome <span class="bio-muted font-normal">(opcional)</span>
                                </span>
                            </label>
                            <input type="text" name="_submitter_name" id="_submitter_name" value="{{ old('_submitter_name') }}" class="form-input">
                        </div>
                        <div>
                            <label for="_submitter_email" class="form-label">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined" style="font-size:15px">mail</span>
                                    Seu e-mail <span class="bio-muted font-normal">(opcional)</span>
                                </span>
                            </label>
                            <input type="email" name="_submitter_email" id="_submitter_email" value="{{ old('_submitter_email') }}" class="form-input">
                        </div>
                    </div>

                    @foreach($template->fields as $field)
                    <div>
                        <label for="field_{{ $field->id }}" class="form-label">{{ $field->label }}{{ $field->required ? ' *' : '' }}</label>

                        @if($field->type === 'text')
                            <input type="text" name="{{ $field->name_key }}" id="field_{{ $field->id }}" value="{{ old($field->name_key) }}"
                                   {{ $field->required ? 'required' : '' }} class="form-input"
                                   @if($field->name_key === 'cpf') data-mask="cpf" maxlength="14" placeholder="000.000.000-00" @endif>

                        @elseif($field->type === 'textarea')
                            <textarea name="{{ $field->name_key }}" id="field_{{ $field->id }}" rows="4"
                                      {{ $field->required ? 'required' : '' }} class="form-input">{{ old($field->name_key) }}</textarea>

                        @elseif($field->type === 'number')
                            <input type="number" name="{{ $field->name_key }}" id="field_{{ $field->id }}" value="{{ old($field->name_key) }}"
                                   {{ $field->required ? 'required' : '' }} class="form-input">

                        @elseif($field->type === 'date')
                            <input type="text" name="{{ $field->name_key }}" id="field_{{ $field->id }}" value="{{ old($field->name_key) }}"
                                   {{ $field->required ? 'required' : '' }} class="form-input flatpickr-date" placeholder="dd/mm/aaaa" autocomplete="off">

                        @elseif($field->type === 'select')
                            <select name="{{ $field->name_key }}" id="field_{{ $field->id }}"
                                    {{ $field->required ? 'required' : '' }} class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($field->getOptionsList() as $opt)
                                    <option value="{{ $opt }}" {{ old($field->name_key) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>

                        @elseif($field->type === 'radio')
                            <div class="space-y-2 mt-1">
                                @foreach($field->getOptionsList() as $opt)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="{{ $field->name_key }}" value="{{ $opt }}"
                                               {{ old($field->name_key) === $opt ? 'checked' : '' }}
                                               class="form-checkbox rounded-full">
                                        <span class="text-sm bio-text">{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif($field->type === 'checkbox')
                            <label class="flex items-center gap-2 cursor-pointer mt-1">
                                <input type="checkbox" name="{{ $field->name_key }}" value="1"
                                       {{ old($field->name_key) ? 'checked' : '' }} class="form-checkbox">
                                <span class="text-sm bio-text">Sim</span>
                            </label>

                        @elseif($field->type === 'file')
                            <input type="file" name="{{ $field->name_key }}" id="field_{{ $field->id }}" accept=".pdf,.jpg,.jpeg,.png,.gif" class="form-input" data-show-details="true">
                            <div class="file-selected-details mt-2 text-sm rounded-lg border p-3 hidden border bio-border bio-bg-soft" id="file-details-field_{{ $field->id }}" data-for="field_{{ $field->id }}" aria-live="polite"></div>
                            <p class="text-xs bio-muted mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size:14px">info</span>
                                PDF ou imagem, até 5 MB
                            </p>

                        @elseif($field->type === 'signature')
                            <div class="border bio-border rounded-xl p-3 bio-bg-soft" style="border-width:1px">
                                <p class="text-xs bio-muted mb-2">Ao assinar, você declara estar ciente de que a assinatura digital tem a mesma validade jurídica que a assinatura de próprio punho.</p>
                                <canvas id="canvas_{{ $field->name_key }}" width="400" height="150"
                                        class="border bio-border rounded-lg bio-bg touch-none w-full form-signature-canvas"
                                        style="max-width:100%;height:auto;border-width:1px"></canvas>
                                <input type="hidden" name="_signature[{{ $field->name_key }}]" id="input_signature_{{ $field->name_key }}">
                                <button type="button" onclick="clearSignature('{{ $field->name_key }}')"
                                        class="mt-2 inline-flex items-center gap-1 text-sm bio-muted hover:opacity-80 transition-opacity">
                                    <span class="material-symbols-outlined" style="font-size:16px">refresh</span>
                                    Limpar assinatura
                                </button>
                            </div>
                        @endif

                        @error($field->name_key)<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endforeach

                    {{-- Botão Enviar: no desktop fica aqui; no mobile fica na bottom bar --}}
                    <div class="pt-4 hidden md:block">
                        <button type="submit" id="btn-submit" class="btn-primary">
                            <span class="material-symbols-outlined" style="font-size:20px">send</span>
                            Enviar formulário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bottom bar (mobile): Enviar + Tema --}}
    <div class="form-bottom-bar fixed bottom-0 left-0 right-0 z-20 flex items-center gap-3 md:hidden" style="border-top-width:1px">
        <button type="button" id="mobile-submit" class="btn-primary flex-1 min-w-0">
            <span class="material-symbols-outlined" style="font-size:20px">send</span>
            Enviar formulário
        </button>
        <button type="button" onclick="var b=document.body;b.classList.toggle('dark');try{localStorage.setItem('zionmed_form_dark_mode',b.classList.contains('dark')?'1':'0')}catch(e){}" class="btn-theme shrink-0 w-11 h-11 rounded-xl flex items-center justify-center" data-tooltip="Alternar tema" aria-label="Alternar tema">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>
    </div>

    <script>
    (function() {
        var SUBMITTER_KEY = 'zionmed_form_submitter';
        function loadSubmitterFromStorage() {
            try {
                var raw = localStorage.getItem(SUBMITTER_KEY);
                if (!raw) return;
                var data = JSON.parse(raw);
                var nameInp = document.getElementById('_submitter_name');
                var emailInp = document.getElementById('_submitter_email');
                if (nameInp && data.name && !nameInp.value.trim()) nameInp.value = data.name;
                if (emailInp && data.email && !emailInp.value.trim()) emailInp.value = data.email;
            } catch (e) {}
        }
        function saveSubmitterToStorage() {
            try {
                var nameInp = document.getElementById('_submitter_name');
                var emailInp = document.getElementById('_submitter_email');
                var name = nameInp ? nameInp.value.trim() : '';
                var email = emailInp ? emailInp.value.trim() : '';
                if (name || email) {
                    localStorage.setItem(SUBMITTER_KEY, JSON.stringify({ name: name, email: email }));
                }
            } catch (e) {}
        }
        loadSubmitterFromStorage();
        var form = document.getElementById('public-form');
        var mobileSubmit = document.getElementById('mobile-submit');
        if (mobileSubmit) {
            mobileSubmit.addEventListener('click', function() {
                if (form) form.requestSubmit();
            });
        }
        form.addEventListener('submit', function() {
            saveSubmitterToStorage();
            @foreach($template->fields as $field)
            @if($field->type === 'signature')
            (function() {
                var c = document.getElementById('canvas_{{ $field->name_key }}');
                var inp = document.getElementById('input_signature_{{ $field->name_key }}');
                if (c && inp) inp.value = c.toDataURL('image/png');
            })();
            @endif
            @endforeach
        });

        document.querySelectorAll('input[data-mask="cpf"]').forEach(function(inp) {
            inp.addEventListener('input', function() {
                var v = this.value.replace(/\D/g, '');
                if (v.length > 11) v = v.slice(0, 11);
                this.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, function(_, a, b, c, d) {
                    return (a ? a + (b ? '.' + b : '') : '') + (c ? '.' + c : '') + (d ? '-' + d : '');
                });
            });
        });

        function getSignatureColor() {
            return document.body.classList.contains('dark') ? '#e8e8f0' : '#1a1a2e';
        }
        var canvases = {};
        @foreach($template->fields as $field)
        @if($field->type === 'signature')
        (function() {
            var nameKey = '{{ $field->name_key }}';
            var c = document.getElementById('canvas_' + nameKey);
            if (!c) return;
            var ctx = c.getContext('2d');
            ctx.strokeStyle = getSignatureColor();
            ctx.lineWidth = 2;
            var drawing = false;
            var last = null;
            function getPos(e) {
                var rect = c.getBoundingClientRect();
                var scaleX = c.width / rect.width, scaleY = c.height / rect.height;
                var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                var clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
            }
            function draw(e) {
                e.preventDefault();
                if (!drawing) return;
                var p = getPos(e);
                if (last) { ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke(); }
                last = p;
            }
            function start(e) { e.preventDefault(); drawing = true; last = getPos(e); }
            function end(e) { e.preventDefault(); drawing = false; last = null; updateSignature(nameKey, c); }
            c.addEventListener('mousedown', start); c.addEventListener('mousemove', draw); c.addEventListener('mouseup', end); c.addEventListener('mouseleave', end);
            c.addEventListener('touchstart', start, { passive: false }); c.addEventListener('touchmove', draw, { passive: false }); c.addEventListener('touchend', end);
            canvases[nameKey] = c;
        })();
        @endif
        @endforeach

        function clearSignature(nameKey) {
            var c = document.getElementById('canvas_' + nameKey);
            var inp = document.getElementById('input_signature_' + nameKey);
            if (c) { var ctx = c.getContext('2d'); ctx.clearRect(0, 0, c.width, c.height); }
            if (inp) inp.value = '';
        }
        window.clearSignature = clearSignature;

        function updateSignature(nameKey, canvas) {
            var data = canvas.toDataURL('image/png');
            var inp = document.getElementById('input_signature_' + nameKey);
            if (inp) inp.value = data;
        }

        // Detalhes do arquivo selecionado (qualquer campo type=file)
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        document.querySelectorAll('.file-selected-details[data-for]').forEach(function(detailsEl) {
            var inputId = detailsEl.getAttribute('data-for');
            var input = document.getElementById(inputId);
            if (!input || input.type !== 'file') return;
            input.addEventListener('change', function() {
                var file = this.files && this.files[0];
                if (!file) {
                    detailsEl.classList.add('hidden');
                    detailsEl.innerHTML = '';
                    return;
                }
                var isImage = file.type.indexOf('image/') === 0;
                var html = '<div class="flex items-center gap-3 flex-wrap">';
                if (isImage) {
                    var url = URL.createObjectURL(file);
                    html += '<img src="' + url + '" alt="" class="max-h-14 rounded border bio-border object-contain" aria-hidden="true">';
                }
                html += '<div><span class="font-medium bio-text">' + (file.name || 'Arquivo') + '</span>';
                html += '<p class="text-xs bio-muted mt-0.5">' + formatFileSize(file.size) + ' • ' + (file.type || '') + '</p></div></div>';
                detailsEl.innerHTML = html;
                detailsEl.classList.remove('hidden');
            });
        });
    })();
    </script>
</body>
</html>
