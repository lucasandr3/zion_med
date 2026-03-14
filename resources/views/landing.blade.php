<!doctype html>
<html lang="pt-BR" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ZionMed — Fichas e Consentimentos Digitais para Clínicas</title>
  <meta name="description" content="Digitalize fichas e consentimentos da sua clínica: pacientes recebem um link, preenchem antes da consulta e assinam digitalmente. Protocolo, PDF automático e histórico organizado. Trial grátis." />
  <link rel="canonical" href="{{ url()->current() }}" />

  <!-- Open Graph -->
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="ZionMed" />
  <meta property="og:title" content="ZionMed — Fichas e Consentimentos Digitais para Clínicas" />
  <meta property="og:description" content="Digitalize fichas e consentimentos da sua clínica: pacientes recebem um link, preenchem antes da consulta e assinam digitalmente. Protocolo, PDF automático e histórico organizado. Trial grátis." />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:locale" content="pt_BR" />
  <meta property="og:image" content="{{ asset('assets/images/logo/zionmed_logo.png') }}" />

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="ZionMed — Fichas e Consentimentos Digitais para Clínicas" />
  <meta name="twitter:description" content="Digitalize fichas e consentimentos da sua clínica: pacientes recebem um link, preenchem antes da consulta e assinam digitalmente. Protocolo, PDF automático e histórico organizado. Trial grátis." />
  <meta name="twitter:image" content="{{ asset('assets/images/logo/zionmed_logo.png') }}" />

  <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
  <meta name="apple-mobile-web-app-title" content="ZionMed" />
  <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

  <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin />

  <!-- Tailwind via CDN (MVP). Em produção, prefira build com tailwind.config. -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- JSON-LD: Organization + WebSite -->
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@graph": [
      {
        "@@type": "Organization",
        "@@id": "{{ url('/') }}#organization",
        "name": "ZionMed",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('assets/images/logo/zionmed_logo.png') }}",
        "description": "Fichas e consentimentos digitais para clínicas: pacientes preenchem e assinam antes da consulta. Protocolo, PDF automático e histórico organizado."
      },
      {
        "@@type": "WebSite",
        "name": "ZionMed",
        "url": "{{ url('/') }}",
        "description": "Fichas e consentimentos digitais para clínicas — sem papel na recepção.",
        "publisher": { "@@id": "{{ url('/') }}#organization" }
      }
    ]
  }
  </script>

  <script>
    // Tailwind config inline (CDN) — Zion Blue (#1e40af, #2563eb)
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            base: {
              950: "#020617",
              900: "#0b1220",
              850: "#0f172a",
              800: "#111c33",
              700: "#1f2a44",
              200: "#e5e7eb",
              100: "#f3f4f6",
              50:  "#f8fafc",
            },
            accent: {
              500: "#2563eb",
              600: "#1e40af",
            },
            emeraldish: {
              500: "#34d399",
              600: "#10b981",
            }
          },
          boxShadow: {
            soft: "0 10px 30px rgba(2, 6, 23, 0.12)",
            glow: "0 0 0 1px rgba(30,64,175,0.25), 0 10px 30px rgba(30,64,175,0.12)",
          }
        }
      }
    }
  </script>

  <style>
    /* Modo escuro: mesma chave do app (zionmed_dark_mode) */
    .dark body { background: #0f172a !important; color: #e2e8f0 !important; }
    .dark .bg-base-50 { background-color: #0f172a !important; }
    .dark .bg-white { background-color: #1e293b !important; }
    .dark .bg-base-100 { background-color: #1e293b !important; }
    .dark .border-base-200 { border-color: #334155 !important; }
    .dark .text-slate-950, .dark .text-slate-900, .dark .text-slate-800, .dark .text-slate-700 { color: #f1f5f9 !important; }
    .dark .text-slate-600 { color: #cbd5e1 !important; }
    .dark .text-slate-500 { color: #94a3b8 !important; }
    .dark .bg-base-950 { background-color: #020617 !important; }
    .dark .bg-base-900:hover { background-color: #0b1220 !important; }
    .dark .bg-base-50\/80 { background-color: rgba(15,23,42,0.8) !important; }
    .dark .border-base-200\/60 { border-color: rgba(51,65,85,0.6) !important; }
    .dark input, .dark textarea { background-color: #1e293b !important; border-color: #334155 !important; color: #f1f5f9 !important; }
    .dark input::placeholder, .dark textarea::placeholder { color: #94a3b8 !important; }
    .dark details summary { color: #f1f5f9 !important; }
    .dark .rounded-2xl.border.bg-base-50 { background-color: #1e293b !important; }
    /* Menu do header no dark: texto visível e hover claro */
    .dark header nav a { color: #cbd5e1 !important; }
    .dark header nav a:hover { color: #f1f5f9 !important; }
    /* Botão Entrar no dark: fundo e hover visíveis */
    .dark .btn-entrar { background-color: #1e293b !important; color: #e2e8f0 !important; border-color: #334155 !important; }
    .dark .btn-entrar:hover { background-color: #334155 !important; color: #f1f5f9 !important; border-color: #475569 !important; }
    /* Botões de destaque no dark: Zion Blue para contraste */
    .dark .btn-cta-primary { background-color: #2563eb !important; color: #fff !important; }
    .dark .btn-cta-primary:hover { background-color: #1d4ed8 !important; color: #fff !important; }
    /* Faixa topo, CTA final e footer: Zion Blue no dark */
    .dark .bg-accent-600 { background-color: #1e40af !important; }
    .dark #demo { background-color: #1e40af !important; }
    .dark footer.bg-accent-600 { background-color: #1e3a8a !important; }
    /* Subtle grain */
    .grain:before{
      content:"";
      position:absolute; inset:0;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.12'/%3E%3C/svg%3E");
      mix-blend-mode: overlay;
      opacity:.35;
      pointer-events:none;
    }
  </style>
</head>

<body class="bg-base-50 text-base-950 antialiased">
  <!-- Top announcement — status dinâmico -->
  @php
      $ss = $serviceStatus ?? ['status' => 'operational', 'severity' => 'none', 'message' => null];
      $ssIsOk = ($ss['status'] ?? 'operational') === 'operational';
      $ssLabels = [
          'operational' => 'Todos os sistemas operacionais',
          'degraded'    => 'Desempenho degradado',
          'outage'      => 'Interrupção nos serviços',
          'maintenance' => 'Manutenção em andamento',
      ];
      $ssDots = [
          'operational' => 'bg-emeraldish-500',
          'degraded'    => 'bg-yellow-400',
          'outage'      => 'bg-red-400',
          'maintenance' => 'bg-indigo-400',
      ];
      $ssBgs = [
          'operational' => 'bg-accent-600',
          'degraded'    => 'bg-yellow-600',
          'outage'      => 'bg-red-600',
          'maintenance' => 'bg-indigo-600',
      ];
      $ssKey = $ss['status'] ?? 'operational';
  @endphp
  <div class="{{ $ssBgs[$ssKey] ?? 'bg-accent-600' }} text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs sm:text-sm">
      <a href="{{ route('status') }}" class="flex items-center gap-2 hover:underline">
        <span class="inline-flex h-2 w-2 rounded-full {{ $ssDots[$ssKey] ?? 'bg-emeraldish-500' }}"></span>
        {{ $ssLabels[$ssKey] ?? 'Todos os sistemas operacionais' }}
        @if(!$ssIsOk && !empty($ss['message']))
          <span class="hidden sm:inline text-white/70">— {{ Str::limit($ss['message'], 60) }}</span>
        @endif
      </a>

      <button type="button" id="themeToggle" aria-label="Alternar tema"
        class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/20">
        <span id="themeToggleLabel">Modo escuro</span>
      </button>
    </div>
  </div>

  <!-- Header -->
  <header class="sticky top-0 z-40 border-b border-base-200/60 bg-base-50/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
      <div class="flex items-center gap-3">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden bg-accent-600 shadow-glow p-1">
            <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med" class="w-full h-full object-contain rounded-lg">
          </div>
          <div class="leading-tight">
            <div class="text-sm font-semibold tracking-tight">ZionMed</div>
            <div class="text-xs text-slate-500">Fichas e Consentimentos Digitais</div>
          </div>
        </a>
      </div>

      <nav class="hidden items-center gap-6 text-sm text-slate-600 md:flex">
        <a href="#como-funciona" class="hover:text-slate-900">Como funciona</a>
        <a href="#beneficios" class="hover:text-slate-900">Benefícios</a>
        <a href="#governanca" class="hover:text-slate-900">Governança</a>
        <a href="#integracoes" class="hover:text-slate-900">Integrações</a>
        <a href="#precos" class="hover:text-slate-900">Planos</a>
      </nav>

      <div class="flex items-center gap-2">
        <a href="{{ route('login') }}"
          class="btn-entrar rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-base-100 border border-base-200">
          Entrar
        </a>
        <a href="{{ route('comece.show') }}"
          class="btn-cta-primary inline-flex items-center justify-center rounded-lg bg-accent-600 px-4 py-2 text-sm font-semibold text-white shadow-soft hover:bg-accent-500">
          Começar trial
        </a>
      </div>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative overflow-hidden bg-base-50">
    <div class="grain pointer-events-none absolute inset-0"></div>
    <div class="mx-auto max-w-7xl px-4 py-16 sm:py-20">
      <div class="grid items-center gap-10 lg:grid-cols-2">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full border border-base-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm">
            <span class="h-2 w-2 rounded-full bg-emeraldish-500"></span>
            Fichas e consentimentos 100% digitais para clínicas
          </div>

          <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
            Elimine o papel da<br class="hidden sm:block" />
            recepção da sua clínica.
          </h1>

          <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
            Pacientes recebem um link, preenchem a ficha antes da consulta e assinam digitalmente o consentimento.
            Sua clínica recebe tudo com protocolo, PDF automático e histórico organizado — sem trocar o sistema atual.
          </p>

          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
            <a id="cta"
              href="{{ route('comece.show') }}"
              class="btn-cta-primary inline-flex items-center justify-center rounded-lg bg-accent-600 px-6 py-3 text-sm font-semibold text-white shadow-soft hover:bg-accent-500">
              Começar teste grátis
            </a>
            <a href="#demo"
              class="inline-flex items-center justify-center rounded-lg border border-base-200 bg-white px-6 py-3 text-sm font-semibold text-slate-800 hover:bg-base-100">
              Ver demonstração
            </a>
          </div>

          <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-base-200 bg-white p-4 shadow-sm">
              <div class="text-sm font-semibold">Sem papel</div>
              <div class="mt-1 text-xs text-slate-500">Fichas e consentimentos 100% digitais</div>
            </div>
            <div class="rounded-xl border border-base-200 bg-white p-4 shadow-sm">
              <div class="text-sm font-semibold">Assinatura + PDF</div>
              <div class="mt-1 text-xs text-slate-500">Registro formal com protocolo automático</div>
            </div>
            <div class="rounded-xl border border-base-200 bg-white p-4 shadow-sm">
              <div class="text-sm font-semibold">Histórico organizado</div>
              <div class="mt-1 text-xs text-slate-500">Documentos fáceis de localizar e exportar</div>
            </div>
          </div>
        </div>

        <!-- Hero visual: fluxo do paciente -->
        <div class="relative">
          <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-soft">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-900">Fluxo do paciente</div>
                <div class="text-xs text-slate-500">Do link até o protocolo final</div>
              </div>
              <div class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">
                100% digital
              </div>
            </div>

            <div class="mt-6 space-y-3">
              <div class="flex items-start gap-3 rounded-xl border border-base-200 bg-base-50 p-4">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-600 text-xs font-bold text-white">1</div>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Recepção envia o link</div>
                  <div class="mt-0.5 text-xs text-slate-500">Via WhatsApp, SMS ou e-mail, antes da consulta</div>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded-xl border border-base-200 bg-base-50 p-4">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-600 text-xs font-bold text-white">2</div>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Paciente preenche a ficha</div>
                  <div class="mt-0.5 text-xs text-slate-500">No celular ou computador, sem instalar nada</div>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded-xl border border-base-200 bg-base-50 p-4">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-600 text-xs font-bold text-white">3</div>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Assina digitalmente</div>
                  <div class="mt-0.5 text-xs text-slate-500">Consentimento com data/hora e evidência formal</div>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded-xl border border-emeraldish-500/30 bg-emeraldish-500/5 p-4">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emeraldish-600 text-xs font-bold text-white">4</div>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Clínica recebe protocolo e PDF</div>
                  <div class="mt-0.5 text-xs text-slate-500">Tudo organizado com trilha de auditoria</div>
                </div>
              </div>
            </div>

            <div class="mt-5 rounded-xl border border-base-200 bg-white p-4">
              <div class="flex items-center justify-between text-xs text-slate-500">
                <span>Paciente: A*** S***</span>
                <span class="rounded-full bg-emeraldish-600/10 px-2 py-0.5 text-xs font-medium text-emeraldish-600">Protocolo #10482 · PDF gerado</span>
              </div>
            </div>
          </div>

          <div class="pointer-events-none absolute -bottom-8 -right-8 hidden h-40 w-40 rounded-full bg-accent-600/10 blur-2xl lg:block"></div>
          <div class="pointer-events-none absolute -top-10 -left-10 hidden h-40 w-40 rounded-full bg-emeraldish-600/10 blur-2xl lg:block"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Trust strip -->
  <section class="border-y border-base-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8">
      <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <div class="text-sm font-semibold text-slate-900">Complementar ao ERP e prontuário que você já usa</div>
          <div class="text-sm text-slate-600">O ZionMed não substitui nada — ele digitaliza o que ainda está em papel na recepção.</div>
        </div>
        <div class="flex flex-wrap gap-2">
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">Fichas digitais</span>
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">Consentimentos</span>
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">Assinatura digital</span>
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">PDF automático</span>
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">Protocolo</span>
          <span class="rounded-full border border-base-200 bg-base-50 px-3 py-1 text-xs font-medium text-slate-600">Auditoria</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Problema -->
  <section id="problema" class="bg-base-50">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
          Sua clínica ainda depende de papel para fichas e consentimentos?
        </h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
          Papéis na recepção parecem inofensivos, mas no dia a dia trazem lentidão, perda de informação e risco jurídico.
          O ZionMed foi criado para resolver exatamente esses problemas.
        </p>
      </div>

      <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Fichas em papel na recepção</div>
          <p class="mt-2 text-sm text-slate-600">Pacientes preenchem tudo na hora, formando fila e causando retrabalho à equipe.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 text-orange-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Consentimentos sem padrão</div>
          <p class="mt-2 text-sm text-slate-600">Cada profissional usando um modelo diferente, sem controle de versão nem registro formal.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Documentos difíceis de localizar</div>
          <p class="mt-2 text-sm text-slate-600">Pastas físicas, arquivos espalhados e busca manual que consome tempo precioso da equipe.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Risco jurídico por falta de comprovação</div>
          <p class="mt-2 text-sm text-slate-600">Assinaturas ilegíveis, datas imprecisas e documentos sem cópia digital geram exposição legal.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Tempo da equipe consumido com papelada</div>
          <p class="mt-2 text-sm text-slate-600">Impressão, coleta, arquivamento e busca manual drenam a produtividade da recepção.</p>
        </div>

        <div class="flex flex-col justify-between rounded-2xl border border-accent-600/20 bg-accent-600/5 p-6 shadow-sm">
          <div>
            <div class="text-sm font-semibold text-accent-600">O ZionMed resolve isso</div>
            <p class="mt-2 text-sm text-slate-600">Fichas e consentimentos digitais, assinatura com evidência, PDF automático e histórico organizado — sem trocar o sistema atual.</p>
          </div>
          <a href="{{ route('comece.show') }}" class="btn-cta-primary mt-6 inline-flex items-center justify-center rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-500">
            Começar teste grátis
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section id="como-funciona" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
          Como o ZionMed funciona na prática
        </h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
          Em quatro passos simples, sua clínica digitaliza fichas e consentimentos, mantendo tudo organizado com evidência jurídica — sem trocar o sistema atual.
        </p>
      </div>

      <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-600 text-sm font-bold text-white">1</div>
          <div class="mt-4 text-base font-semibold text-slate-900">A clínica envia um link</div>
          <p class="mt-2 text-sm text-slate-600">
            A recepção gera o link do formulário e envia ao paciente por WhatsApp, SMS ou e-mail antes da consulta.
          </p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-600 text-sm font-bold text-white">2</div>
          <div class="mt-4 text-base font-semibold text-slate-900">Paciente preenche a ficha</div>
          <p class="mt-2 text-sm text-slate-600">
            Ele acessa no celular ou computador, sem instalar nada, e preenche os dados com comodidade antes de chegar.
          </p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-600 text-sm font-bold text-white">3</div>
          <div class="mt-4 text-base font-semibold text-slate-900">Assina o consentimento</div>
          <p class="mt-2 text-sm text-slate-600">
            O paciente lê e assina digitalmente. O ZionMed registra data/hora, IP e evidências de forma segura.
          </p>
        </div>

        <div class="rounded-2xl border border-emeraldish-500/30 bg-emeraldish-500/5 p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emeraldish-600 text-sm font-bold text-white">4</div>
          <div class="mt-4 text-base font-semibold text-slate-900">Clínica recebe protocolo e PDF</div>
          <p class="mt-2 text-sm text-slate-600">
            Cada envio gera protocolo único, PDF automático e entra no histórico — pronto para consulta e auditoria.
          </p>
        </div>
      </div>

      <div class="mt-10 rounded-2xl border border-base-200 bg-base-50 p-6">
        <div class="grid gap-6 lg:grid-cols-3">
          <div>
            <div class="text-xs font-medium text-slate-500">Casos de uso incluídos</div>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
              <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-600"></span> Ficha de cadastro do paciente</li>
              <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-600"></span> Termo de consentimento com assinatura</li>
              <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-600"></span> Anamnese e triagem antes da consulta</li>
              <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-600"></span> Checklist de sala e equipamentos</li>
              <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-600"></span> Pesquisa de satisfação do paciente (NPS)</li>
            </ul>
          </div>
          <div class="lg:col-span-2">
            <div class="text-xs font-medium text-slate-500">O que muda na rotina da clínica</div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div class="rounded-xl border border-base-200 bg-white p-4">
                <div class="text-sm font-semibold">Recepção mais ágil</div>
                <div class="mt-1 text-sm text-slate-600">Paciente chega com ficha preenchida e consentimento assinado.</div>
              </div>
              <div class="rounded-xl border border-base-200 bg-white p-4">
                <div class="text-sm font-semibold">Documentos sempre acessíveis</div>
                <div class="mt-1 text-sm text-slate-600">Localização rápida por paciente, data, tipo ou protocolo.</div>
              </div>
              <div class="rounded-xl border border-base-200 bg-white p-4">
                <div class="text-sm font-semibold">Evidência jurídica</div>
                <div class="mt-1 text-sm text-slate-600">Assinatura digital com protocolo e registro formal de cada documento.</div>
              </div>
              <div class="rounded-xl border border-base-200 bg-white p-4">
                <div class="text-sm font-semibold">Sem trocar o ERP</div>
                <div class="mt-1 text-sm text-slate-600">Complementar ao sistema atual, sem migração ou substituição.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Benefícios -->
  <section id="beneficios" class="bg-base-50">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
          Benefícios diretos para a rotina da clínica
        </h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
          Menos papel, recepção mais rápida e documentação organizada com evidência jurídica.
        </p>
      </div>

      <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emeraldish-500/10 text-emeraldish-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Menos papel e menos impressões</div>
          <p class="mt-2 text-sm text-slate-600">Fichas e consentimentos digitais reduzem custo com impressão, arquivo físico e risco de perda de documentos.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-accent-600/10 text-accent-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Recepção mais rápida</div>
          <p class="mt-2 text-sm text-slate-600">Paciente chega com ficha preenchida e consentimento assinado — a equipe só precisa confirmar e atender.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Documentos organizados e fáceis de localizar</div>
          <p class="mt-2 text-sm text-slate-600">Protocolos e PDFs organizados por paciente, data e tipo de formulário — encontrados em segundos.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Protocolo e PDF automáticos</div>
          <p class="mt-2 text-sm text-slate-600">Cada envio gera automaticamente protocolo único, data/hora e PDF — sem nenhum trabalho manual.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Proteção jurídica reforçada</div>
          <p class="mt-2 text-sm text-slate-600">Assinaturas digitais com evidência e trilha de auditoria reduzem a exposição em questionamentos futuros.</p>
        </div>

        <div class="rounded-2xl border border-base-200 bg-white p-6 shadow-sm">
          <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          </div>
          <div class="text-sm font-semibold text-slate-900">Auditoria e rastreabilidade completas</div>
          <p class="mt-2 text-sm text-slate-600">Logs por usuário, setor e período mostram quem fez o quê, quando e em qual documento.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Demo visual -->
  <section id="demonstracao-video" class="bg-white border-y border-base-200">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
            Veja o ZionMed funcionando em menos de 1 minuto
          </h2>
          <p class="mt-4 text-base leading-relaxed text-slate-600">
            Veja o fluxo completo: do envio do link para o paciente até o protocolo e PDF gerados na clínica.
          </p>
          <ul class="mt-6 space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">
              <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emeraldish-600 text-white text-xs">✓</span>
              Paciente recebendo e preenchendo o formulário no celular
            </li>
            <li class="flex gap-3">
              <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emeraldish-600 text-white text-xs">✓</span>
              Assinatura digital e geração do consentimento
            </li>
            <li class="flex gap-3">
              <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emeraldish-600 text-white text-xs">✓</span>
              Protocolo, PDF e histórico disponíveis na clínica
            </li>
          </ul>
          <a href="#demo" class="btn-cta-primary mt-8 inline-flex items-center gap-2 rounded-lg bg-accent-600 px-6 py-3 text-sm font-semibold text-white hover:bg-accent-500">
            Agendar uma demonstração
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </div>
        <div class="aspect-video rounded-2xl border border-base-200 bg-base-50 flex items-center justify-center shadow-soft">
          {{-- Substitua pelo embed do vídeo quando disponível --}}
          <div class="text-center p-8">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-accent-600/10 text-accent-600">
              <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <p class="mt-4 text-sm font-medium text-slate-700">Demonstração do ZionMed</p>
            <p class="mt-1 text-xs text-slate-500">Vídeo disponível em breve</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Governance -->
  <section id="governanca" class="bg-base-50">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="grid gap-10 lg:grid-cols-2">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
            Governança e auditoria para além das fichas e consentimentos.
          </h2>
          <p class="mt-4 text-base leading-relaxed text-slate-600">
            Depois que fichas e consentimentos estão 100% digitais, sua clínica passa a contar com uma camada de governança:
            perfis com permissões claras, fluxos de aprovação e trilha de auditoria completa por documento, usuário e período.
          </p>

            <div class="mt-8 space-y-4">
            <div class="flex gap-3">
              <div class="mt-1 h-8 w-8 rounded-lg bg-accent-600 text-white grid place-items-center text-sm font-semibold">1</div>
              <div>
                <div class="text-sm font-semibold">Perfis e permissões</div>
                <div class="text-sm text-slate-600">Owner, Manager e Staff com acessos claros por função.</div>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="mt-1 h-8 w-8 rounded-lg bg-accent-600 text-white grid place-items-center text-sm font-semibold">2</div>
              <div>
                <div class="text-sm font-semibold">Fluxo de aprovação</div>
                <div class="text-sm text-slate-600">Pendência → aprovação/reprovação com comentário e responsável.</div>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="mt-1 h-8 w-8 rounded-lg bg-accent-600 text-white grid place-items-center text-sm font-semibold">3</div>
              <div>
                <div class="text-sm font-semibold">Trilha de auditoria</div>
                <div class="text-sm text-slate-600">Registro de ações e eventos por usuário, data e contexto.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm font-semibold text-slate-900">Exemplo de auditoria</div>
              <div class="text-xs text-slate-500">Registro interno (exemplo visual)</div>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 border border-base-200">
              Audit log
            </span>
          </div>

          <div class="mt-5 space-y-3">
            <div class="rounded-xl border border-base-200 bg-white p-4">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-700">Aprovação de protocolo</span>
                <span class="text-slate-500">Hoje · 10:42</span>
              </div>
              <div class="mt-2 text-sm text-slate-600">
                Usuário <span class="font-medium">Manager</span> aprovou protocolo <span class="font-medium">#10482</span>.
              </div>
            </div>
            <div class="rounded-xl border border-base-200 bg-white p-4">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-700">Criação de template</span>
                <span class="text-slate-500">Ontem · 18:12</span>
              </div>
              <div class="mt-2 text-sm text-slate-600">
                Owner criou template <span class="font-medium">"Checklist Sala — Abertura"</span>.
              </div>
            </div>
            <div class="rounded-xl border border-base-200 bg-white p-4">
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-slate-700">Protocolo público</span>
                <span class="text-slate-500">Ontem · 09:01</span>
              </div>
              <div class="mt-2 text-sm text-slate-600">
                Novo termo assinado via link público (token). PDF gerado automaticamente.
              </div>
            </div>
          </div>

          <div class="mt-6 rounded-xl border border-base-200 bg-white p-4">
            <div class="text-xs font-medium text-slate-500">Conformidade</div>
            <div class="mt-2 flex items-center gap-3">
              <div class="h-2 flex-1 rounded-full bg-base-200">
                <div class="h-2 w-[82%] rounded-full bg-emeraldish-600"></div>
              </div>
              <div class="text-sm font-semibold">82%</div>
            </div>
            <div class="mt-2 text-xs text-slate-500">Percentual de checklists completos no período selecionado.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Security -->
  <section id="seguranca" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="grid gap-10 lg:grid-cols-2">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
            Segurança e controle de acesso para dados sensíveis de pacientes.
          </h2>
          <p class="mt-4 text-base leading-relaxed text-slate-600">
            Como lidamos com dados sensíveis de pacientes e documentos com impacto jurídico, o ZionMed foi desenhado
            com foco em segurança: permissões por perfil, links com token seguro e registro completo de auditoria.
          </p>

          <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-base-200 bg-base-50 p-5">
              <div class="text-sm font-semibold">Permissões por perfil</div>
              <div class="mt-1 text-sm text-slate-600">Owner, Manager e Staff com regras claras.</div>
            </div>
            <div class="rounded-2xl border border-base-200 bg-base-50 p-5">
              <div class="text-sm font-semibold">Tokens públicos longos</div>
              <div class="mt-1 text-sm text-slate-600">Links controlados, com possibilidade de revogação.</div>
            </div>
            <div class="rounded-2xl border border-base-200 bg-base-50 p-5">
              <div class="text-sm font-semibold">Assinatura e evidência</div>
              <div class="mt-1 text-sm text-slate-600">PDF gerado automaticamente com protocolo.</div>
            </div>
            <div class="rounded-2xl border border-base-200 bg-base-50 p-5">
              <div class="text-sm font-semibold">Auditoria</div>
              <div class="mt-1 text-sm text-slate-600">Log de ações para rastrear operações internas.</div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 shadow-sm">
          <div class="text-sm font-semibold text-slate-900">Checklist de conformidade (visão)</div>
          <p class="mt-2 text-sm text-slate-600">
            Itens típicos que sua gestão precisa responder em auditorias internas:
          </p>
          <ul class="mt-4 space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">
              <span class="mt-1 h-5 w-5 rounded-md bg-emeraldish-600 text-white grid place-items-center text-xs">✓</span>
              Quem aprovou este documento e quando?
            </li>
            <li class="flex gap-3">
              <span class="mt-1 h-5 w-5 rounded-md bg-emeraldish-600 text-white grid place-items-center text-xs">✓</span>
              Existe evidência formal (assinatura, PDF e protocolo)?
            </li>
            <li class="flex gap-3">
              <span class="mt-1 h-5 w-5 rounded-md bg-emeraldish-600 text-white grid place-items-center text-xs">✓</span>
              O acesso é controlado por perfil e setor?
            </li>
            <li class="flex gap-3">
              <span class="mt-1 h-5 w-5 rounded-md bg-emeraldish-600 text-white grid place-items-center text-xs">✓</span>
              O documento pode ser exportado quando solicitado?
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Integrações -->
  <section id="integracoes" class="bg-white border-t border-base-200">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
          O ZionMed integra com outros sistemas
        </h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
          Quem assina o ZionMed pode conectá-lo ao ERP, prontuário ou outros sistemas da clínica por meio de <strong>API REST</strong> e <strong>webhooks</strong>.
          Assim, o fluxo de fichas e consentimentos conversa com o que você já usa hoje, sem precisar trocar nada.
        </p>
      </div>
      <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent-600/10 text-accent-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          </div>
          <h3 class="mt-4 text-sm font-semibold text-slate-900">API REST</h3>
          <p class="mt-2 text-sm text-slate-600">Consulte protocolos e templates, com filtros e paginação. Autenticação por token (Bearer).</p>
        </div>
        <div class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent-600/10 text-accent-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          </div>
          <h3 class="mt-4 text-sm font-semibold text-slate-900">Webhooks</h3>
          <p class="mt-2 text-sm text-slate-600">Receba eventos em tempo real (novo protocolo, aprovado, reprovado) na URL do seu sistema.</p>
        </div>
        <div class="rounded-2xl border border-base-200 bg-base-50 p-6 sm:col-span-2 lg:col-span-1">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent-600/10 text-accent-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          </div>
          <h3 class="mt-4 text-sm font-semibold text-slate-900">Documentação para terceiros</h3>
          <p class="mt-2 text-sm text-slate-600">Assinantes têm acesso à documentação interativa (OpenAPI) e à geração de tokens no painel.</p>
          <a href="{{ route('login') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-accent-600 hover:text-accent-600/90">
            Acessar documentação (após login)
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing (planos e trial vêm do banco via config merge) -->
  @php
    $landingPlans = config('asaas.plans', []);
    $landingTrialDays = (int) config('asaas.trial_days', 14);
    $planKeys = array_keys($landingPlans);
  @endphp
  <section id="precos" class="bg-base-50">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
          Um plano simples para começar a digitalizar sua documentação.
        </h2>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
          Comece com fichas e consentimentos digitais, assinatura, protocolo e PDF automático.
          Trial de {{ $landingTrialDays }} dias sem cartão de crédito.
        </p>
      </div>

      <div class="mt-10 grid gap-6 lg:grid-cols-3">
        @foreach($landingPlans as $planKey => $planData)
          @php
            $isRecommended = count($planKeys) >= 2 && $planKey === $planKeys[1];
            $name = $planData['name'] ?? $planKey;
            $value = (float) ($planData['value'] ?? 0);
            $description = $planData['description'] ?? '';
          @endphp
          <div class="rounded-2xl border p-6 shadow-sm {{ $isRecommended ? 'border-accent-600/30 bg-accent-600 text-white shadow-glow' : 'border-base-200 bg-white' }}">
            <div class="flex items-center justify-between">
              <div class="text-sm font-semibold {{ $isRecommended ? '' : 'text-slate-900' }}">{{ $name }}</div>
              @if($isRecommended)
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-medium">Recomendado</span>
              @endif
            </div>
            <div class="mt-2 text-3xl font-semibold">
              R$ {{ number_format($value, 2, ',', '.') }}<span class="text-base font-medium {{ $isRecommended ? 'text-white/80' : 'text-slate-500' }}">/mês</span>
            </div>
            @if($description)
              <div class="mt-1 text-sm {{ $isRecommended ? 'text-white/80' : 'text-slate-500' }}">{{ $description }}</div>
            @endif

            <a href="{{ route('comece.show', ['plan' => $planKey]) }}" class="mt-8 inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold {{ $isRecommended ? 'btn-cta-primary bg-white text-accent-600 hover:bg-white/95' : 'border border-base-200 bg-base-50 text-slate-800 hover:bg-base-100' }}">
              Começar teste grátis
            </a>
          </div>
        @endforeach
      </div>

      @if(empty($landingPlans))
        <div class="mt-10 rounded-2xl border border-base-200 bg-white p-8 text-center text-slate-500">
          <p>Nenhum plano disponível no momento. Entre em contato para mais informações.</p>
        </div>
      @endif

      <div class="mt-8 space-y-2 text-sm text-slate-500">
        <p>* Trial de {{ $landingTrialDays }} dias sem cartão de crédito. Após o cadastro, sua clínica testa o ZionMed na prática e só continua se fizer sentido para a operação.</p>
        <p>* A implantação inicial é dimensionada conforme o número de unidades, volume de documentos e necessidade de integração. Clínica pequena: R$ 500–R$ 1.500. Com fluxos e treinamento: R$ 1.500–R$ 3.000. Multiunidade ou integrações: R$ 3.000–R$ 6.000.</p>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <h2 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Perguntas frequentes</h2>
      <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">O ZionMed substitui meu ERP ou prontuário?</summary>
          <p class="mt-3 text-sm text-slate-600">
            Não. O ZionMed é complementar: ele digitaliza fichas, consentimentos e processos operacionais, com assinatura, protocolo e PDF automático — sem mexer no faturamento ou prontuário que você já usa.
          </p>
        </details>

        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">O paciente precisa instalar algum aplicativo?</summary>
          <p class="mt-3 text-sm text-slate-600">
            Não. Ele acessa o formulário por um link, no navegador do celular ou computador, preenche e assina digitalmente em poucos minutos — sem instalar nada.
          </p>
        </details>

        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">Os documentos gerados têm protocolo e data/hora?</summary>
          <p class="mt-3 text-sm text-slate-600">
            Sim. Cada envio gera protocolo único, data/hora e um PDF automático vinculado ao formulário e à assinatura do paciente.
          </p>
        </details>

        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">Consigo localizar rapidamente um documento assinado?</summary>
          <p class="mt-3 text-sm text-slate-600">
            Sim. Você pode buscar por paciente, data, tipo de formulário ou protocolo, e visualizar o histórico completo daquele documento em segundos.
          </p>
        </details>

        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">Dá para usar links públicos com segurança?</summary>
          <p class="mt-3 text-sm text-slate-600">
            Sim. Links são gerados com token longo e podem ser revogados a qualquer momento. Aplicamos também rate limit para reduzir abuso.
          </p>
        </details>

        <details class="rounded-2xl border border-base-200 bg-base-50 p-6">
          <summary class="cursor-pointer text-sm font-semibold text-slate-900">Quanto tempo leva para implantar?</summary>
          <p class="mt-3 text-sm text-slate-600">
            A base pode ser configurada rapidamente — você já consegue usar fichas e consentimentos básicos logo no início do trial. O prazo total depende da quantidade de templates, fluxos e unidades envolvidas.
          </p>
        </details>
      </div>
    </div>
  </section>

  <!-- JSON-LD: FAQPage -->
  <script type="application/ld+json">
  {
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
      {
        "@@type": "Question",
        "name": "O ZionMed substitui meu ERP ou prontuário?",
        "acceptedAnswer": { "@@type": "Answer", "text": "Não. O ZionMed é complementar: ele digitaliza fichas, consentimentos e processos operacionais, com assinatura, protocolo e PDF automático — sem mexer no faturamento ou prontuário que você já usa." }
      },
      {
        "@@type": "Question",
        "name": "O paciente precisa instalar algum aplicativo?",
        "acceptedAnswer": { "@@type": "Answer", "text": "Não. Ele acessa o formulário por um link, no navegador do celular ou computador, preenche e assina digitalmente em poucos minutos — sem instalar nada." }
      },
      {
        "@@type": "Question",
        "name": "Os documentos gerados têm protocolo e data/hora?",
        "acceptedAnswer": { "@@type": "Answer", "text": "Sim. Cada envio gera protocolo único, data/hora e um PDF automático vinculado ao formulário e à assinatura do paciente." }
      },
      {
        "@@type": "Question",
        "name": "Consigo localizar rapidamente um documento assinado?",
        "acceptedAnswer": { "@@type": "Answer", "text": "Sim. Você pode buscar por paciente, data, tipo de formulário ou protocolo, e visualizar o histórico completo daquele documento em segundos." }
      },
      {
        "@@type": "Question",
        "name": "Dá para usar links públicos com segurança?",
        "acceptedAnswer": { "@@type": "Answer", "text": "Sim. Links são gerados com token longo e podem ser revogados a qualquer momento. Aplicamos também rate limit para reduzir abuso." }
      },
      {
        "@@type": "Question",
        "name": "Quanto tempo leva para implantar?",
        "acceptedAnswer": { "@@type": "Answer", "text": "A base pode ser configurada rapidamente — você já consegue usar fichas e consentimentos básicos logo no início do trial. O prazo total depende da quantidade de templates, fluxos e unidades envolvidas." }
      }
    ]
  }
  </script>

  <!-- Final CTA -->
  <section id="demo" class="bg-accent-600 text-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
      <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
            Veja como sua clínica pode digitalizar fichas e consentimentos sem trocar o sistema atual.
          </h2>
          <p class="mt-4 text-base leading-relaxed text-white/75">
            Em uma demonstração rápida, mostramos o fluxo completo: envio do link para o paciente, preenchimento da ficha, assinatura digital, protocolo e PDF automático.
          </p>

          <div class="mt-8 flex flex-wrap gap-3">
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">Fichas digitais</span>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">Consentimentos</span>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">Assinatura digital</span>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">PDF automático</span>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">Protocolo</span>
          </div>
        </div>

        <div class="rounded-2xl bg-white p-6 text-slate-900 shadow-soft">
          <div class="text-sm font-semibold">Quero ver uma demonstração do ZionMed</div>
          <p class="mt-1 text-sm text-slate-600">Preencha o formulário ou fale direto pelo WhatsApp. Vamos entender sua clínica e mostrar como o ZionMed se encaixa na rotina atual.</p>

          <div class="mt-4 flex flex-wrap items-center gap-3">
            <a href="https://wa.me/5534996460818?text=Olá! Gostaria de agendar uma demonstração do ZionMed."
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#20BD5A] transition-colors">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
              WhatsApp (34) 99646-0818
            </a>
            <span class="text-xs text-slate-500">Resposta rápida</span>
          </div>

          <div class="mt-6 border-t border-slate-200 pt-6">
            <p class="text-xs font-medium text-slate-700 mb-3">Ou preencha o formulário:</p>
          </div>

          <div id="form-demonstracao-feedback" class="mt-4 hidden" role="alert"></div>

          @if (session('demonstracao_sucesso'))
          <div class="mt-6 mb-4 rounded-lg bg-emeraldish-500/15 px-4 py-3 text-sm text-emeraldish-600 dark:text-emeraldish-400" id="form-demonstracao-session-success">
            {{ session('demonstracao_sucesso') }}
          </div>
          @endif
          <form id="form-demonstracao" class="mt-6 space-y-4" action="{{ route('demonstracao.store') }}" method="post">
            @csrf
            @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
              <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
            @endif
            <div>
              <label class="text-xs font-medium text-slate-700">Nome</label>
              <input class="mt-1 w-full rounded-lg border border-base-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-600/30"
                     type="text" name="name" value="{{ old('name') }}" placeholder="Seu nome" required />
            </div>

            <div>
              <label class="text-xs font-medium text-slate-700">Clínica</label>
              <input class="mt-1 w-full rounded-lg border border-base-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-600/30"
                     type="text" name="clinic" value="{{ old('clinic') }}" placeholder="Nome da clínica" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="text-xs font-medium text-slate-700">E-mail</label>
                <input class="mt-1 w-full rounded-lg border border-base-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-600/30"
                       type="email" name="email" value="{{ old('email') }}" placeholder="email@clinica.com" required />
              </div>
              <div>
                <label class="text-xs font-medium text-slate-700">WhatsApp</label>
                <input id="form-demonstracao-phone" class="mt-1 w-full rounded-lg border border-base-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-600/30"
                       type="tel" name="phone" value="{{ old('phone') }}" placeholder="(00) 00000-0000" maxlength="16" required />
              </div>
            </div>

            <div>
              <label class="text-xs font-medium text-slate-700">Mensagem</label>
              <textarea class="mt-1 w-full rounded-lg border border-base-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-600/30"
                        name="message" rows="3" placeholder="Conte rapidamente seu cenário (unidades, volume, processos).">{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="btn-cta-primary inline-flex w-full items-center justify-center rounded-lg bg-accent-600 px-4 py-3 text-sm font-semibold text-white hover:bg-accent-500">
              Quero ver uma demonstração
            </button>

            <p class="text-xs text-slate-500">
              Ao enviar, você concorda em ser contatado para agendamento da demonstração. Não enviamos spam.
            </p>
          </form>
        </div>
      </div>
    </div>
  </section>

  <footer class="bg-accent-600 text-white/90">
    <div class="mx-auto max-w-7xl px-4 py-10">
      <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 overflow-hidden bg-accent-600 p-1">
            <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med" class="w-full h-full object-contain rounded-lg">
          </div>
          <div>
            <div class="text-sm font-semibold text-white">ZionMed</div>
            <div class="text-xs text-white/60">Fichas e Consentimentos Digitais para Clínicas</div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4 text-xs">
          <a href="{{ route('status') }}" class="text-white/70 hover:text-white transition-colors">Status do serviço</a>
          <span class="hidden sm:inline text-white/40">·</span>
          <a href="{{ route('privacidade') }}" class="text-white/70 hover:text-white transition-colors">Política de Privacidade</a>
          <span class="hidden sm:inline text-white/40">·</span>
          <a href="{{ route('termos') }}" class="text-white/70 hover:text-white transition-colors">Termos de Uso</a>
          <span class="hidden sm:inline text-white/40">·</span>
          <span>© <span id="year"></span> ZionMed. Todos os direitos reservados.</span>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // year
    document.getElementById("year").textContent = new Date().getFullYear();

    // Tema: mesma chave do app (zionmed_dark_mode) — 1 = modo escuro, 0/ausente = modo claro
    const themeBtn = document.getElementById("themeToggle");
    const themeLabel = document.getElementById("themeToggleLabel");
    const root = document.documentElement;

    function applyTheme(isDark) {
      if (isDark) {
        root.classList.add("dark");
        localStorage.setItem("zionmed_dark_mode", "1");
        if (themeLabel) themeLabel.textContent = "Modo claro";
      } else {
        root.classList.remove("dark");
        localStorage.setItem("zionmed_dark_mode", "0");
        if (themeLabel) themeLabel.textContent = "Modo escuro";
      }
    }

    (function initTheme() {
      const saved = localStorage.getItem("zionmed_dark_mode");
      const isDark = saved === "1";
      if (isDark) {
        root.classList.add("dark");
        if (themeLabel) themeLabel.textContent = "Modo claro";
      } else {
        root.classList.remove("dark");
        if (themeLabel) themeLabel.textContent = "Modo escuro";
      }
    })();

    themeBtn.addEventListener("click", function() {
      applyTheme(!root.classList.contains("dark"));
    });

    // Máscara WhatsApp no formulário de demonstração: (XX) XXXXX-XXXX
    (function() {
      const phoneInput = document.getElementById("form-demonstracao-phone");
      if (!phoneInput) return;

      function formatPhone(value) {
        var digits = (value || "").replace(/\D/g, "").slice(0, 11);
        if (digits.length <= 2) return digits ? "(" + digits : "";
        if (digits.length <= 7) return "(" + digits.slice(0, 2) + ") " + digits.slice(2);
        return "(" + digits.slice(0, 2) + ") " + digits.slice(2, 7) + "-" + digits.slice(7);
      }

      function formatPhoneFull(value) {
        var digits = (value || "").replace(/\D/g, "").slice(0, 11);
        if (digits.length === 0) return "";
        if (digits.length <= 2) return "(" + digits;
        if (digits.length <= 7) return "(" + digits.slice(0, 2) + ") " + digits.slice(2);
        return "(" + digits.slice(0, 2) + ") " + digits.slice(2, 7) + "-" + digits.slice(7);
      }

      if (phoneInput.value) {
        phoneInput.value = formatPhoneFull(phoneInput.value);
      }

      phoneInput.addEventListener("input", function() {
        var start = this.selectionStart;
        var prevLen = this.value.length;
        var digits = this.value.replace(/\D/g, "").slice(0, 11);
        this.value = formatPhone(digits);
        var newLen = this.value.length;
        var newStart = Math.max(0, Math.min(start + (newLen - prevLen), this.value.length));
        this.setSelectionRange(newStart, newStart);
      });
    })();

    // Formulário demonstração: envio via fetch (sem recarregar)
    (function() {
      const form = document.getElementById("form-demonstracao");
      const feedback = document.getElementById("form-demonstracao-feedback");
      const sessionSuccess = document.getElementById("form-demonstracao-session-success");
      if (!form || !feedback) return;

      form.addEventListener("submit", async function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = "Enviando…";
        feedback.classList.add("hidden");
        feedback.innerHTML = "";

        const formData = new FormData(form);
        const csrf = form.querySelector('input[name="_token"]');
        const url = form.getAttribute("action");

        try {
          const res = await fetch(url, {
            method: "POST",
            body: formData,
            headers: {
              "X-Requested-With": "XMLHttpRequest",
              "Accept": "application/json",
            },
          });
          const data = await res.json().catch(() => ({}));

          if (res.ok && data.success) {
            feedback.className = "mt-6 rounded-lg bg-emeraldish-500/15 px-4 py-3 text-sm text-emeraldish-600 dark:text-emeraldish-400";
            feedback.textContent = data.message;
            feedback.classList.remove("hidden");
            form.reset();
            if (sessionSuccess) sessionSuccess.classList.add("hidden");
          } else if (res.status === 422 && data.errors) {
            const list = Object.values(data.errors).flat().join("\n");
            feedback.className = "mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300";
            feedback.innerHTML = "<ul class=\"list-inside list-disc\">" + list.split("\n").map(function(t) { return "<li>" + escapeHtml(t) + "</li>"; }).join("") + "</ul>";
            feedback.classList.remove("hidden");
          } else {
            feedback.className = "mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300";
            feedback.textContent = data.message || "Não foi possível enviar. Tente novamente.";
            feedback.classList.remove("hidden");
          }
        } catch (err) {
          feedback.className = "mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300";
          feedback.textContent = "Erro de conexão. Tente novamente.";
          feedback.classList.remove("hidden");
        }

        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });

      function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
      }
    })();
  </script>
</body>
</html>
