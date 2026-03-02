@extends('layouts.app')

@section('title', 'Escolher empresa')

@section('content')
<style>
  .card-enter { animation: cardIn 0.35s ease both; }
  @keyframes cardIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .escolher-clinica-card:hover { transform: translateY(-2px); }
  .escolher-clinica-card {
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    border: 2px solid var(--c-border);
  }
  .escolher-clinica-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.09);
    border-color: var(--c-muted);
  }
  .escolher-clinica-card .arrow-hint { transition: opacity 0.15s, transform 0.15s; opacity: 0; }
  .escolher-clinica-card:hover .arrow-hint { opacity: 1; transform: translateX(2px); }
</style>
<div class="flex-1 flex flex-col overflow-hidden">
  <div class="flex-1 overflow-y-auto">
    {{-- Page Header --}}
    <div class="mb-8">
      <div class="flex items-start justify-between gap-3 mb-1">
        <div class="flex items-start gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: var(--c-soft);">
            <svg class="w-5 h-5" style="color: var(--c-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-bold leading-tight" style="color: var(--c-text)">Escolher empresa</h1>
            <p class="text-sm mt-0.5" style="color: var(--c-muted)">Selecione a empresa que deseja acessar. Você pode trocar a qualquer momento pelo menu.</p>
          </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
          @csrf
          <button type="submit"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                  style="color: var(--c-muted); border: 1px solid var(--c-border); background: transparent;"
                  data-tooltip="Sair" aria-label="Sair"
                  onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.borderColor='rgba(239,68,68,0.35)';this.style.color='#ef4444'"
                  onmouseout="this.style.background='transparent';this.style.borderColor='var(--c-border)';this.style.color='var(--c-muted)'">
            <span class="material-symbols-outlined" style="font-size:18px">logout</span>
            Sair
          </button>
        </form>
      </div>

      {{-- Search --}}
      <div class="relative max-w-xs mt-5">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--c-muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          id="search-clinics"
          type="text"
          placeholder="Buscar empresa..."
          class="w-full pr-4 py-2 text-sm rounded-xl transition-all form-input"
          style="border-radius: 0.75rem; padding-left: 2.5rem;"
        />
      </div>
    </div>

    {{-- Cards Grid --}}
    <div id="clinics-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      @forelse($clinics as $index => $clinic)
        @php
          $gradients = [
            'from-blue-500 to-blue-600',
            'from-emerald-500 to-emerald-600',
            'from-violet-500 to-violet-600',
          ];
          $gradient = $gradients[$index % 3];
          $initial = mb_strtoupper(mb_substr($clinic->name, 0, 1));
          $isLastAccess = $currentClinicId && (int) $currentClinicId === (int) $clinic->id;
        @endphp
        <form action="{{ route('clinica.escolher.store') }}" method="POST" class="contents">
          @csrf
          <input type="hidden" name="clinic_id" value="{{ $clinic->id }}">
          <input type="hidden" name="redirect_after" value="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">
          <button
            type="submit"
            class="escolher-clinica-card card-enter relative w-full text-left rounded-2xl p-5 cursor-pointer border-2 bg-white dark:bg-(--c-surface)"
            style="animation-delay: {{ $index * 80 }}ms; background-color: var(--c-surface);"
            data-clinic-name="{{ strtolower($clinic->name) }}"
            data-clinic-email="{{ strtolower($clinic->notification_email ?? '') }}"
            data-clinic-address="{{ strtolower($clinic->address ?? '') }}"
          >
            @if($isLastAccess)
              <span class="absolute top-4 right-4 text-[11px] font-semibold px-2.5 py-0.5 rounded-full" style="background: var(--c-soft); color: var(--c-primary)">Último acesso</span>
            @endif

            {{-- Avatar + Name --}}
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl bg-linear-to-br {{ $gradient }} flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-md">
                {{ $initial }}
              </div>
              <div class="min-w-0 pr-6">
                <p class="font-semibold text-sm leading-snug truncate" style="color: var(--c-text)">{{ $clinic->name }}</p>
                @if($clinic->notification_email)
                  <p class="text-xs mt-0.5 truncate" style="color: var(--c-muted)">{{ $clinic->notification_email }}</p>
                @endif
              </div>
            </div>

            {{-- Divider --}}
            <div class="border-t mb-4" style="border-color: var(--c-border); opacity: 0.5"></div>

            {{-- Endereço --}}
            @if(!empty($clinic->address))
            <div class="flex items-center gap-2 text-xs mb-2" style="color: var(--c-muted)">
              <svg class="w-3.5 h-3.5 shrink-0" style="color: var(--c-muted); opacity: 0.8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span>{{ $clinic->address }}</span>
            </div>
            @endif

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-3 border-t" style="border-color: var(--c-border); opacity: 0.5">
              <div class="flex items-center gap-1.5 text-xs" style="color: var(--c-text)">
                <svg class="w-3.5 h-3.5 shrink-0" style="color: var(--c-text)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $clinic->users_count }} {{ $clinic->users_count === 1 ? 'usuário' : 'usuários' }}
              </div>
              <div class="flex items-center gap-1 text-xs font-semibold arrow-hint" style="color: var(--c-primary)">
                Acessar
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </div>
            </div>
          </button>
        </form>
      @empty
        <div class="col-span-full text-center py-12" style="color: var(--c-muted)">
          Nenhuma clínica cadastrada.
        </div>
      @endforelse
    </div>

    {{-- Empty state (busca) --}}
    <div id="clinics-empty" class="hidden text-center py-20">
      <svg class="w-10 h-10 mx-auto mb-3" style="color: var(--c-muted); opacity: 0.6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <p class="text-sm" style="color: var(--c-muted)">
        Nenhuma clínica encontrada para "<span id="clinics-empty-term" class="font-semibold"></span>"
      </p>
    </div>
  </div>
</div>

@push('page-scripts')
<script>
(function () {
  var searchEl = document.getElementById('search-clinics');
  var gridEl = document.getElementById('clinics-grid');
  var emptyEl = document.getElementById('clinics-empty');
  var emptyTermEl = document.getElementById('clinics-empty-term');
  var cards = gridEl ? gridEl.querySelectorAll('button[data-clinic-name]') : [];

  function filterCards(term) {
    var q = (term || '').toLowerCase().trim();
    var visible = 0;
    cards.forEach(function (card) {
      var name = (card.getAttribute('data-clinic-name') || '').toLowerCase();
      var email = (card.getAttribute('data-clinic-email') || '').toLowerCase();
      var address = (card.getAttribute('data-clinic-address') || '').toLowerCase();
      var show = !q || name.indexOf(q) !== -1 || email.indexOf(q) !== -1 || address.indexOf(q) !== -1;
      card.closest('form').style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (emptyEl && emptyTermEl) {
      if (visible === 0) {
        emptyEl.classList.remove('hidden');
        emptyTermEl.textContent = term || '';
      } else {
        emptyEl.classList.add('hidden');
      }
    }
  }

  if (searchEl) {
    searchEl.addEventListener('input', function () { filterCards(this.value); });
  }
})();
</script>
@endpush
@endsection
