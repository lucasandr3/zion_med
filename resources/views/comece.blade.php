<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Começar trial — ZionMed</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
  <meta name="apple-mobile-web-app-title" content="ZionMed" />
  <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            accent: { 500: "#2563eb", 600: "#1e40af" },
            base: { 50: "#f8fafc", 100: "#f3f4f6", 200: "#e5e7eb", 950: "#020617" },
          },
        },
      },
    };
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    .comece-card { box-shadow: 0 10px 30px rgba(2, 6, 23, 0.08), 0 0 0 1px rgba(0,0,0,0.04); }
    .plan-radio:checked + label { border-color: #4f46e5; background-color: #eef2ff; }
    .plan-radio:checked + label .plan-check { display: flex; }
    .plan-radio:checked + label .plan-name { color: #4338ca; }
    .strength-bar { transition: width 0.3s ease, background-color 0.3s ease; }
  </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen">

  {{-- Header --}}
  <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur sticky top-0 z-10">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4">
      <a href="{{ route('home') }}" class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden bg-indigo-600 p-1">
          <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med" class="w-full h-full object-contain rounded-lg">
        </div>
        <div class="leading-tight">
          <div class="text-sm font-semibold tracking-tight text-slate-900">ZionMed</div>
          <div class="hidden text-xs text-slate-500 sm:block">Governança e Segurança Documental</div>
        </div>
      </a>
      <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('home') }}#precos" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 sm:inline-flex">Ver planos</a>
        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Entrar
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-5 sm:py-7">

    {{-- Stepper --}}
    <div class="mb-5 flex items-center justify-center gap-2 sm:gap-4">
      <div class="flex items-center gap-2">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">1</span>
        <span class="text-sm font-semibold text-indigo-700">Cadastro</span>
      </div>
      <div class="h-px w-8 sm:w-16 bg-slate-200"></div>
      <div class="flex items-center gap-2 opacity-40">
        <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-slate-300 text-xs font-bold text-slate-400">2</span>
        <span class="text-sm font-medium text-slate-400">Configuração</span>
      </div>
      <div class="h-px w-8 sm:w-16 bg-slate-200"></div>
      <div class="flex items-center gap-2 opacity-40">
        <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-slate-300 text-xs font-bold text-slate-400">3</span>
        <span class="text-sm font-medium text-slate-400">Pronto!</span>
      </div>
    </div>

    {{-- Layout 2 colunas --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_480px] xl:grid-cols-[1fr_520px]">

      {{-- COLUNA ESQUERDA — Social proof e benefícios --}}
      <div class="flex flex-col gap-6 lg:py-2">

        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Governança documental para clínicas que levam a sério.
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            A ZionMed centraliza formulários, evidências e trilhas de auditoria para que sua clínica opere com segurança, conformidade e rastreabilidade — sem burocracia extra.
          </p>
        </div>

        {{-- Benefícios --}}
        <div class="space-y-3">
          @foreach([
            ['icon' => 'verified_user',  'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50',  'title' => 'Documentação com força jurídica',      'desc' => 'Formulários assinados digitalmente com trilha de auditoria imutável.'],
            ['icon' => 'bolt',           'color' => 'text-amber-500',   'bg' => 'bg-amber-50',   'title' => 'Acesso imediato, sem burocracia',        'desc' => 'Sua conta é criada na hora. Comece a usar em minutos, sem instalação.'],
            ['icon' => 'assignment',     'color' => 'text-sky-500',     'bg' => 'bg-sky-50',     'title' => 'Fluxos prontos para clínicas',           'desc' => 'Templates de formulários e checklists prontos para uso desde o primeiro dia.'],
            ['icon' => 'notifications',  'color' => 'text-rose-500',    'bg' => 'bg-rose-50',    'title' => 'Alertas e notificações automáticas',     'desc' => 'Saiba em tempo real quando documentos venceram ou precisam ser renovados.'],
            ['icon' => 'corporate_fare', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50', 'title' => 'Multi-unidade e multi-equipe',           'desc' => 'Gerencie várias unidades e equipes com permissões granulares por perfil.'],
          ] as $benefit)
          <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $benefit['bg'] }}">
              <span class="material-icons-round text-[20px] {{ $benefit['color'] }}">{{ $benefit['icon'] }}</span>
            </span>
            <div>
              <p class="text-sm font-semibold text-slate-800">{{ $benefit['title'] }}</p>
              <p class="text-sm text-slate-500 mt-0.5">{{ $benefit['desc'] }}</p>
            </div>
          </div>
          @endforeach
        </div>

        {{-- Badges de confiança --}}
        <div class="flex flex-wrap gap-2 pt-2">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            {{ (int) config('asaas.trial_days', 14) }} dias grátis
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Sem cartão de crédito
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Acesso liberado na hora
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            Cancele quando quiser
          </span>
        </div>

        {{-- Informativo de implantação (colapsável) --}}
        <details class="rounded-xl border border-amber-200 bg-amber-50">
          <summary class="flex cursor-pointer items-center justify-between p-4 text-sm font-semibold text-amber-900 select-none">
            <span class="flex items-center gap-2">
              <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Informações sobre implantação
            </span>
            <svg class="h-4 w-4 text-amber-600 transition-transform details-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div class="border-t border-amber-200 px-4 pb-4 pt-3 text-sm text-amber-800 leading-relaxed">
            A conta e o trial são liberados imediatamente. A implantação inicial varia conforme escopo:
            <ul class="mt-2 space-y-1 list-none">
              <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span> <span><strong>R$ 500 – R$ 1.500</strong> para clínica pequena</span></li>
              <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span> <span><strong>R$ 1.500 – R$ 3.000</strong> para operação com fluxos e treinamento</span></li>
              <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span> <span><strong>R$ 3.000 – R$ 6.000</strong> para multiunidade ou integrações</span></li>
            </ul>
            <a href="{{ route('home') }}#precos" class="mt-3 inline-block font-medium text-amber-700 hover:underline">Ver detalhes completos dos planos →</a>
          </div>
        </details>

        {{-- O que acontece depois --}}
        <div class="rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900">
          <p class="font-semibold flex items-center gap-2">
            <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            O que acontece depois do cadastro?
          </p>
          <p class="mt-1.5 text-sky-800 leading-relaxed">
            Sua conta é criada e o trial começa na hora. Se a cobrança não for configurada automaticamente neste passo, você poderá concluir a ativação comercial depois, dentro da plataforma.
          </p>
        </div>

        {{-- Suporte --}}
        <div class="flex items-center gap-3 text-sm text-slate-500">
          <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.557 4.126 1.526 5.855L0 24l6.335-1.504A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.797 9.797 0 01-5.003-1.374l-.36-.213-3.724.884.939-3.617-.234-.372A9.78 9.78 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
          Dúvidas? Fale com a equipe pelo
          <a href="https://wa.me/5500000000000?text=Ol%C3%A1%2C+tenho+interesse+no+ZionMed+e+gostaria+de+mais+informa%C3%A7%C3%B5es." target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-600 hover:underline">
            WhatsApp
          </a>
        </div>

      </div>

      {{-- COLUNA DIREITA — Formulário --}}
      <div class="comece-card rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">

        <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
          Criar conta e começar o trial
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Preencha os dados abaixo. Seu trial de {{ (int) config('asaas.trial_days', 14) }} dias começa imediatamente após o cadastro.
        </p>

        @if(session('error'))
          <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
          </div>
        @endif

        @if($errors->any())
          <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold mb-1">Verifique os campos abaixo:</p>
            <ul class="list-disc list-inside space-y-1">
              @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
          </div>
        @endif

        <form method="post" action="{{ route('comece.store') }}" class="mt-5 space-y-5" id="comece-form">
          @csrf

          {{-- Seleção de Plano --}}
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Plano <span class="text-red-500">*</span></label>
            <div class="space-y-2" id="plan-selector">
              @foreach($plans as $key => $plan)
                <div>
                  <input
                    type="radio"
                    name="plan_key"
                    id="plan_{{ $key }}"
                    value="{{ $key }}"
                    class="plan-radio sr-only"
                    {{ (old('plan_key', $selectedPlan) === $key) ? 'checked' : '' }}
                    required
                  >
                  <label for="plan_{{ $key }}" class="flex cursor-pointer items-center justify-between rounded-xl border-2 border-slate-200 bg-white px-4 py-3 transition-all hover:border-indigo-300 hover:bg-indigo-50/30">
                    <div class="flex items-center gap-3">
                      <span class="plan-check hidden h-5 w-5 items-center justify-center rounded-full bg-indigo-600 shrink-0">
                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                      </span>
                      <span class="h-5 w-5 rounded-full border-2 border-slate-300 plan-unchecked shrink-0"></span>
                      <div>
                        <p class="text-sm font-semibold text-slate-800 plan-name">{{ $plan['name'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5 leading-snug">{{ $plan['description'] ?? '' }}</p>
                      </div>
                    </div>
                    <p class="text-sm font-bold text-indigo-600 shrink-0 ml-3">R$ {{ number_format($plan['value'] ?? 0, 2, ',', '.') }}<span class="text-xs font-medium text-slate-400">/mês</span></p>
                  </label>
                </div>
              @endforeach
            </div>
          </div>

          <hr class="border-slate-100">

          {{-- Nome da empresa --}}
          <div>
            <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1">Nome da empresa <span class="text-red-500">*</span></label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required autofocus
                   @class(['w-full rounded-lg border px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20', 'border-red-400 focus:border-red-400' => $errors->has('company_name'), 'border-slate-200 focus:border-indigo-500' => !$errors->has('company_name')])
                   placeholder="Ex.: Clínica São Paulo">
            @error('company_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- Nome do responsável --}}
          <div>
            <label for="responsible_name" class="block text-sm font-medium text-slate-700 mb-1">Seu nome (responsável) <span class="text-red-500">*</span></label>
            <input type="text" name="responsible_name" id="responsible_name" value="{{ old('responsible_name') }}" required
                   @class(['w-full rounded-lg border px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20', 'border-red-400 focus:border-red-400' => $errors->has('responsible_name'), 'border-slate-200 focus:border-indigo-500' => !$errors->has('responsible_name')])
                   placeholder="Ex.: Dr. João Silva">
            @error('responsible_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- E-mail --}}
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Seu e-mail <span class="text-red-500">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                   @class(['w-full rounded-lg border px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20', 'border-red-400 focus:border-red-400' => $errors->has('email'), 'border-slate-200 focus:border-indigo-500' => !$errors->has('email')])
                   placeholder="voce@empresa.com">
            <p class="mt-1 text-xs text-slate-500">Usaremos para acesso à conta, trial, cobrança e implantação.</p>
            @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- Senha --}}
          <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Senha <span class="text-red-500">*</span></label>
            <div class="relative">
              <input type="password" name="password" id="password" required minlength="8"
                     @class(['w-full rounded-lg border px-4 py-2.5 pr-11 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20', 'border-red-400 focus:border-red-400' => $errors->has('password'), 'border-slate-200 focus:border-indigo-500' => !$errors->has('password')])
                     placeholder="Crie uma senha segura">
              <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password" aria-label="Mostrar senha">
                <svg class="eye-off h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                <svg class="eye-on h-4 w-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
            {{-- Barra de força da senha --}}
            <div class="mt-2">
              <div class="flex gap-1">
                <div class="h-1 flex-1 rounded-full bg-slate-200 overflow-hidden"><div id="strength-bar-1" class="h-full w-0 rounded-full strength-bar"></div></div>
                <div class="h-1 flex-1 rounded-full bg-slate-200 overflow-hidden"><div id="strength-bar-2" class="h-full w-0 rounded-full strength-bar"></div></div>
                <div class="h-1 flex-1 rounded-full bg-slate-200 overflow-hidden"><div id="strength-bar-3" class="h-full w-0 rounded-full strength-bar"></div></div>
                <div class="h-1 flex-1 rounded-full bg-slate-200 overflow-hidden"><div id="strength-bar-4" class="h-full w-0 rounded-full strength-bar"></div></div>
              </div>
              <p id="strength-label" class="mt-1 text-xs text-slate-400">Use no mínimo 8 caracteres.</p>
            </div>
            @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- Confirmar senha --}}
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar senha <span class="text-red-500">*</span></label>
            <div class="relative">
              <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                     class="w-full rounded-lg border border-slate-200 px-4 py-2.5 pr-11 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                     placeholder="Repita a senha">
              <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-target="password_confirmation" aria-label="Mostrar senha">
                <svg class="eye-off h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                <svg class="eye-on h-4 w-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>

          {{-- Termos --}}
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label for="accepted_terms" class="flex items-start gap-3 text-sm text-slate-600 cursor-pointer">
              <input type="checkbox" name="accepted_terms" id="accepted_terms" value="1" @checked(old('accepted_terms'))
                     class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" required>
              <span>
                Li e concordo com os
                <a href="{{ route('termos') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Termos de Uso</a>
                e a
                <a href="{{ route('privacidade') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">Política de Privacidade</a>.
              </span>
            </label>
          </div>

          {{-- Botão de submit com spinner --}}
          <button type="submit" id="comece-submit" class="w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-indigo-300 transition-colors">
            <svg class="hidden h-4 w-4 animate-spin text-white" data-submit-spinner xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span data-submit-default>Criar conta e iniciar trial</span>
            <span class="hidden" data-submit-loading>Criando conta...</span>
          </button>

        </form>

        <p class="mt-5 text-center text-sm text-slate-500">
          Já tem conta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Entrar</a>
        </p>

        {{-- Segurança --}}
        <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-400">
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Conexão segura · Dados criptografados
        </div>

      </div>
    </div>
  </main>

  <script>
    // Toggle de visibilidade da senha
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetId = btn.getAttribute('data-target');
        var input = document.getElementById(targetId);
        var eyeOff = btn.querySelector('.eye-off');
        var eyeOn = btn.querySelector('.eye-on');
        if (input.type === 'password') {
          input.type = 'text';
          eyeOff.classList.add('hidden');
          eyeOn.classList.remove('hidden');
        } else {
          input.type = 'password';
          eyeOff.classList.remove('hidden');
          eyeOn.classList.add('hidden');
        }
      });
    });

    // Seleção visual do plano (mostrar/esconder círculo)
    document.querySelectorAll('.plan-radio').forEach(function (radio) {
      function updateVisual(r) {
        var lbl = r.nextElementSibling;
        var check = lbl.querySelector('.plan-check');
        var uncheck = lbl.querySelector('.plan-unchecked');
        if (r.checked) {
          check && check.classList.remove('hidden');
          check && check.classList.add('flex');
          uncheck && uncheck.classList.add('hidden');
          lbl.classList.add('border-indigo-500', 'bg-indigo-50');
          lbl.classList.remove('border-slate-200', 'bg-white');
        } else {
          check && check.classList.add('hidden');
          check && check.classList.remove('flex');
          uncheck && uncheck.classList.remove('hidden');
          lbl.classList.remove('border-indigo-500', 'bg-indigo-50');
          lbl.classList.add('border-slate-200', 'bg-white');
        }
      }
      updateVisual(radio);
      radio.addEventListener('change', function () {
        document.querySelectorAll('.plan-radio').forEach(updateVisual);
      });
    });

    // Barra de força da senha
    var passwordInput = document.getElementById('password');
    var strengthBars = [
      document.getElementById('strength-bar-1'),
      document.getElementById('strength-bar-2'),
      document.getElementById('strength-bar-3'),
      document.getElementById('strength-bar-4'),
    ];
    var strengthLabel = document.getElementById('strength-label');
    var strengthColors = ['bg-red-500', 'bg-orange-400', 'bg-yellow-400', 'bg-emerald-500'];
    var strengthTexts = ['Senha muito fraca', 'Senha fraca', 'Senha razoável', 'Senha forte'];

    function calcStrength(pwd) {
      var score = 0;
      if (pwd.length >= 8) score++;
      if (pwd.length >= 12) score++;
      if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
      if (/[0-9]/.test(pwd)) score++;
      if (/[^A-Za-z0-9]/.test(pwd)) score++;
      return Math.min(4, score);
    }

    if (passwordInput) {
      passwordInput.addEventListener('input', function () {
        var val = passwordInput.value;
        if (!val) {
          strengthBars.forEach(function (b) { b.style.width = '0'; b.className = 'h-full w-0 rounded-full strength-bar'; });
          strengthLabel.textContent = 'Use no mínimo 8 caracteres.';
          strengthLabel.className = 'mt-1 text-xs text-slate-400';
          return;
        }
        var score = calcStrength(val);
        strengthBars.forEach(function (b, i) {
          if (i < score) {
            b.style.width = '100%';
            b.className = 'h-full rounded-full strength-bar ' + strengthColors[score - 1];
          } else {
            b.style.width = '0';
            b.className = 'h-full w-0 rounded-full strength-bar';
          }
        });
        var labelColors = ['text-red-500', 'text-orange-500', 'text-yellow-600', 'text-emerald-600'];
        strengthLabel.textContent = strengthTexts[score - 1] || 'Use no mínimo 8 caracteres.';
        strengthLabel.className = 'mt-1 text-xs ' + (labelColors[score - 1] || 'text-slate-400');
      });
    }

    // Submit com spinner e estado de loading
    var comeceForm = document.getElementById('comece-form');
    var submitButton = document.getElementById('comece-submit');

    if (comeceForm && submitButton) {
      comeceForm.addEventListener('submit', function () {
        submitButton.disabled = true;
        submitButton.querySelector('[data-submit-default]')?.classList.add('hidden');
        submitButton.querySelector('[data-submit-loading]')?.classList.remove('hidden');
        submitButton.querySelector('[data-submit-spinner]')?.classList.remove('hidden');
      });
    }
  </script>
</body>
</html>
