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
  <style>
    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    .comece-card { box-shadow: 0 10px 30px rgba(2, 6, 23, 0.08), 0 0 0 1px rgba(0,0,0,0.04); }
  </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen">
  <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
      <a href="{{ route('home') }}" class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden bg-indigo-600 p-1">
          <img src="{{ asset('assets/images/logo/zionmed_logo.png') }}" alt="Zion Med" class="w-full h-full object-contain rounded-lg">
        </div>
        <div class="leading-tight">
          <div class="text-sm font-semibold tracking-tight text-slate-900">ZionMed</div>
          <div class="text-xs text-slate-500">Governança e Segurança Documental</div>
        </div>
      </a>
      <div class="flex items-center gap-2">
        <a href="{{ route('home') }}#precos" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Ver planos</a>
        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Entrar
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-lg px-4 py-12 sm:py-16">
    <div class="comece-card rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
      <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
        Criar conta e começar o trial
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Cadastre sua empresa e receba o boleto por e-mail. Trial de {{ (int) config('asaas.trial_days', 14) }} dias.
      </p>

      @if($selectedPlan && isset($plans[$selectedPlan]))
        @php $plan = $plans[$selectedPlan]; @endphp
        <div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ $plan['name'] }}</p>
              <p class="text-xs text-slate-500 mt-0.5">{{ $plan['description'] ?? '' }}</p>
            </div>
            <p class="text-lg font-bold text-indigo-600">R$ {{ number_format($plan['value'] ?? 0, 2, ',', '.') }}<span class="text-xs font-medium text-slate-500">/mês</span></p>
          </div>
          <p class="mt-2 text-xs text-slate-500">Não gostou do plano? <a href="{{ route('home') }}#precos" class="text-indigo-600 font-medium hover:underline">Trocar na página de planos</a></p>
        </div>
      @endif

      @if($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
          </ul>
        </div>
      @endif

      <form method="post" action="{{ route('comece.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="plan_key" value="{{ $selectedPlan ?? 'core' }}">

        <div>
          <label for="company_name" class="block text-sm font-medium text-slate-700 mb-1">Nome da empresa <span class="text-red-500">*</span></label>
          <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                 class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                 placeholder="Ex.: Clínica São Paulo">
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Seu e-mail <span class="text-red-500">*</span></label>
          <input type="email" name="email" id="email" value="{{ old('email') }}" required
                 class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                 placeholder="voce@empresa.com">
          <p class="mt-1 text-xs text-slate-500">Será usado para login e para receber o boleto.</p>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Senha <span class="text-red-500">*</span></label>
          <input type="password" name="password" id="password" required minlength="8"
                 class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                 placeholder="Mínimo 8 caracteres">
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar senha <span class="text-red-500">*</span></label>
          <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                 class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                 placeholder="Repita a senha">
        </div>

        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
          Criar conta e gerar boleto
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-500">
        Já tem conta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Entrar</a>
      </p>
    </div>
  </main>
</body>
</html>
