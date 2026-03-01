<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $clinic->name }}@if($clinic->short_description) – {{ Str::limit($clinic->short_description, 50) }}@endif</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
  <meta name="apple-mobile-web-app-title" content="ZionMed" />
  <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
  <meta name="description" content="{{ $clinic->meta_description ?? 'Links e informações de ' . $clinic->name }}">
  <meta property="og:title" content="{{ $clinic->name }}">
  <meta property="og:description" content="{{ $clinic->meta_description ?? $clinic->short_description ?? 'Acesse nossos links e formulários' }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">
  @if($clinic->cover_image_path)
    <meta property="og:image" content="{{ asset('storage/' . $clinic->cover_image_path) }}">
  @elseif($clinic->logo_path)
    <meta property="og:image" content="{{ asset('storage/' . $clinic->logo_path) }}">
  @endif

  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    * { font-family: 'Jost', sans-serif; -webkit-font-smoothing: antialiased; }
    h1 { font-family: 'Cormorant Garamond', serif; }

    body {
      background: #f7f5f2;
      min-height: 100vh;
    }
    body.dark {
      background: #0f0f14;
    }

    .logo-wrap {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      background: #1a1a2e;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border: none !important;
      box-shadow: none;
    }
    .logo-wrap:has(img) {
      background: transparent;
      width: auto;
      height: auto;
      min-width: 0;
      min-height: 0;
    }
    .logo-wrap img {
      width: auto;
      height: auto;
      max-width: 64px;
      max-height: 64px;
      border: none !important;
      outline: none;
      box-shadow: none;
      display: block;
    }
    body.dark .logo-wrap { background: #2a2a3e; }

    .link-row {
      border-bottom: 1px solid #e8e4de;
      transition: all 0.2s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.25rem;
    }
    body.dark .link-row { border-color: #2a2a3e; }
    .link-row:last-child { border-bottom: none; }
    .link-row:hover .arrow { transform: translateX(4px); opacity: 1; }
    .link-row:hover { background: #f0ede8; }
    body.dark .link-row:hover { background: #1a1a24; }
    .arrow { transition: all 0.2s ease; opacity: 0.35; }

    .pill {
      background: #e8f5e9;
      color: #2e7d32;
    }
    .pill.closed {
      background: #ffebee;
      color: #c62828;
    }
    body.dark .pill { background: rgba(76,175,80,0.2); color: #81c784; }
    body.dark .pill.closed { background: rgba(244,67,54,0.2); color: #e57373; }

    .dot {
      width: 6px; height: 6px;
      background: #4caf50;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }
    .dot.closed { background: #f44336; animation: none; }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }

    .btn-primary {
      background: #1a1a2e;
      color: #f7f5f2;
      transition: all 0.2s ease;
      border: none;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.625rem;
      width: 100%;
      border-radius: 0.75rem;
      padding: 0.875rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      letter-spacing: 0.025em;
      cursor: pointer;
    }
    body.dark .btn-primary { background: #2a2a3e; color: #e8e8f0; }
    .btn-primary:hover { background: #2c2c4a; }
    body.dark .btn-primary:hover { background: #3a3a52; }

    .btn-wa {
      background: #f7f5f2;
      color: #1a1a2e;
      border: 1px solid #e0dbd3;
      transition: all 0.2s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
    }
    body.dark .btn-wa { background: #1a1a24; color: #e8e8f0; border-color: #2a2a3e; }
    .btn-wa:hover { border-color: #1a1a2e; }
    body.dark .btn-wa:hover { border-color: #4a4a6a; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .f { opacity: 0; animation: fadeUp 0.5s ease forwards; }
    .d1 { animation-delay: 0.05s; }
    .d2 { animation-delay: 0.15s; }
    .d3 { animation-delay: 0.25s; }
    .d4 { animation-delay: 0.35s; }
    .d5 { animation-delay: 0.45s; }
    .d6 { animation-delay: 0.55s; }
    .d7 { animation-delay: 0.65s; }

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
    .bio-icon-bg { background: #f0ede8; }
    body.dark .bio-icon-bg { background: #1e1e2a; }
    .bio-icon-color { color: #5a5650; }
    body.dark .bio-icon-color { color: #8a8a96; }
    .bio-divider { background: #e8e4de; }
    body.dark .bio-divider { background: #2a2a3e; }
    .bio-header-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.5rem;
    }
    .bio-header-actions button {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .bio-header-actions button:hover { opacity: 0.85; }
    .bio-header-actions .btn-share { background: #1a1a2e; color: #f7f5f2; }
    body.dark .bio-header-actions .btn-share { background: #2a2a3e; color: #e8e8f0; }
    .bio-header-actions .btn-theme { background: #f0ede8; color: #5a5650; }
    body.dark .bio-header-actions .btn-theme { background: #1e1e2a; color: #8a8a96; }
  </style>
</head>
<body class="link-bio-public">
  <script>(function(){try{var d=localStorage.getItem('zionmed_bio_dark_mode');if(d==='1')document.body.classList.add('dark');}catch(e){}}());</script>

  {{-- Ações no canto superior direito --}}
  <div class="fixed top-0 left-0 right-0 z-20 flex justify-end px-4 pt-4">
    <div class="bio-header-actions">
      <button type="button" onclick="sharePage()" class="btn-share" data-tooltip="Compartilhar" aria-label="Compartilhar">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
      </button>
      <button type="button" onclick="var b=document.body;b.classList.toggle('dark');try{localStorage.setItem('zionmed_bio_dark_mode',b.classList.contains('dark')?'1':'0')}catch(e){}" class="btn-theme" data-tooltip="Alternar tema" aria-label="Alternar tema">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
      </button>
    </div>
  </div>

  <div class="min-h-screen flex flex-col items-center justify-start pt-16 pb-12 px-5">
    <div class="w-full max-w-sm flex flex-col gap-6">

      {{-- Header --}}
      <div class="f d1 flex flex-col items-center text-center gap-4">
        <div class="logo-wrap">
          @if($clinic->logo_path)
            <img src="{{ asset('storage/' . $clinic->logo_path) }}" alt="{{ $clinic->name }}" style="width:auto;height:auto;border:none;outline:none">
          @else
            <span class="text-2xl font-medium text-[#f7f5f2]" style="font-family:'Cormorant Garamond',serif">{{ mb_strtoupper(mb_substr($clinic->name, 0, 1)) }}</span>
          @endif
        </div>

        <div>
          <h1 class="text-3xl font-light bio-text tracking-wide leading-tight">{{ $clinic->name }}</h1>
          @if($clinic->address || $clinic->short_description)
            <p class="text-xs tracking-[0.2em] bio-muted uppercase mt-1">{{ $clinic->short_description ?? Str::limit($clinic->address, 40) }}</p>
          @endif
        </div>

        @php $isOpen = $clinic->isOpenNow(); $hoursFormatted = $clinic->getBusinessHoursFormatted(); $hoursGrid = $clinic->getBusinessHoursGrid(); @endphp
        @if($isOpen !== null)
          <div class="pill {{ $isOpen ? '' : 'closed' }} flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium">
            <span class="dot {{ $isOpen ? '' : 'closed' }}"></span>
            {{ $isOpen ? 'Aberta agora' : 'Fechada' }}
          </div>
        @endif
      </div>

      {{-- Horários compactos --}}
      @if(!empty($hoursGrid))
        <div class="f d2 h-px bio-divider"></div>
        <div class="f d2 grid grid-cols-7 gap-1 text-center">
          @foreach($hoursGrid as $day)
            <div>
              <p class="text-[10px] bio-muted uppercase tracking-wide">{{ $day['label'] }}</p>
              <p class="text-[11px] bio-text mt-0.5">{{ $day['text'] }}</p>
            </div>
          @endforeach
        </div>
        <div class="f d2 h-px bio-divider"></div>
      @endif

      {{-- WhatsApp e Como chegar --}}
      @if($clinic->phone || $clinic->getMapsUrl())
        <div class="f d3 flex flex-col gap-3">
          <div class="grid grid-cols-2 gap-3">
            @if($clinic->phone)
              @php $wa = preg_replace('/\D/', '', $clinic->phone); $wa = (strlen($wa) >= 10 && strlen($wa) <= 11) ? '55' . $wa : $wa; @endphp
              <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-wa">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                WhatsApp
              </a>
            @endif
            @if($mapsUrl = $clinic->getMapsUrl())
              <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn-wa">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                Como chegar
              </a>
            @endif
          </div>
        </div>
      @endif

      {{-- Links (todos os formulários e links da página) --}}
      @php
        $allLinks = collect();
        foreach ($bioLinks as $lnk) {
          $allLinks->push(['type'=>'bio','item'=>$lnk]);
        }
        foreach ($formLinks as $form) {
          $allLinks->push(['type'=>'form','item'=>$form]);
        }
      @endphp

      @if($allLinks->isNotEmpty())
        <div class="f d4">
          <p class="text-[10px] tracking-[0.2em] bio-muted uppercase mb-3">Links</p>
          <div class="rounded-xl overflow-hidden border bio-border bio-bg">
            @foreach($allLinks as $link)
              @if($link['type'] === 'bio')
                <a href="{{ route('link-bio.out', [$clinic->slug, 'link' => $link['item']->id]) }}" target="_blank" rel="noopener" class="link-row">
                  <div class="w-8 h-8 rounded-lg bio-icon-bg flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-sm bio-icon-color">{{ $link['item']->icon }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium bio-text">{{ $link['item']->label }}</p>
                  </div>
                  <svg class="arrow w-4 h-4 bio-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M9 18l6-6-6-6"/>
                  </svg>
                </a>
              @else
                <a href="{{ route('formulario-publico.show', $link['item']->public_token) }}" class="link-row">
                  <div class="w-8 h-8 rounded-lg bio-icon-bg flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-sm bio-icon-color">description</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium bio-text">{{ $link['item']->name }}</p>
                    @if($link['item']->description)
                      <p class="text-xs bio-muted mt-0.5">{{ Str::limit($link['item']->description, 35) }}</p>
                    @endif
                  </div>
                  <svg class="arrow w-4 h-4 bio-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M9 18l6-6-6-6"/>
                  </svg>
                </a>
              @endif
            @endforeach
          </div>
        </div>
      @endif

      {{-- Footer --}}
      <div class="f d6 text-center pb-4">
        <p class="text-[11px] bio-muted">
          <a href="{{ route('privacidade') }}" target="_blank" rel="noopener" class="hover:underline">Privacidade</a>
          · © {{ date('Y') }} {{ $clinic->name }} · Zion Med
        </p>
      </div>

    </div>
  </div>

  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <script>
  function sharePage() {
    try {
      if (typeof navigator.share === 'function') {
        navigator.share({
          title: document.title,
          text: {!! json_encode($clinic->short_description ?? $clinic->name) !!},
          url: window.location.href
        }).catch(function() { copyLinkFallback(); });
        return;
      }
    } catch (e) {}
    copyLinkFallback();
  }
  function showCopiedFeedback() {
    var btn = document.querySelector('.btn-share');
    if (!btn) return;
    var svg = btn.innerHTML;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>';
    btn.title = 'Copiado!';
    setTimeout(function() { btn.innerHTML = svg; btn.title = 'Compartilhar'; }, 2000);
  }
  function copyLinkFallback() {
    var url = window.location.href;
    function showFeedback() { showCopiedFeedback(); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showFeedback).catch(tryExecCommand);
    } else {
      tryExecCommand();
    }
    function tryExecCommand() {
      var input = document.createElement('input');
      input.value = url;
      input.setAttribute('readonly', '');
      input.style.position = 'absolute';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      input.setSelectionRange(0, 99999);
      try {
        document.execCommand('copy');
        showFeedback();
      } catch (e) {
        alert('Link: ' + url);
      }
      document.body.removeChild(input);
    }
  }
  </script>
</body>
</html>
