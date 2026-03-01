@extends('layouts.app')

@section('title', 'Novo template')
@section('header_back_url', route('templates.index'))
@section('header_back_label', 'Voltar para Templates')

@php
    $allTemplates = collect($templatesByCategory)->flatten(1);
    $categoryKeys = $templatesByCategory->keys()->all();
    $categoryEmoji = [
        'geral' => '📄',
        'clinica_medica' => '🩺',
        'odontologia' => '🦷',
        'estetica' => '✨',
        'fisioterapia' => '💪',
        'psicologia' => '🧠',
        'pediatria' => '👶',
        'ginecologia' => '👩',
        'oftalmologia' => '👁️',
        'dermatologia' => '🧴',
        'laboratorio' => '🔬',
    ];
@endphp

@section('content')
<div class="novo-template-page min-h-full" style="background:var(--c-bg);color:var(--c-text)">
  <style>
    .novo-template-page * { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    .novo-template-page .material-symbols-outlined { font-family: 'Material Symbols Outlined', sans-serif !important; font-weight: 400; }
    .novo-template-page .mono { font-variant-numeric: tabular-nums; }
    .novo-template-page .card {
      background: var(--c-surface);
      border: 1px solid var(--c-border);
      transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
    }
    .novo-template-page .card:hover {
      border-color: var(--c-primary);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px var(--c-focus);
    }
    .novo-template-page .card:hover .card-icon {
      background: var(--c-focus);
      color: var(--c-primary);
    }
    .novo-template-page .card:hover .use-btn {
      background: var(--c-primary);
      color: #fff;
      border-color: var(--c-primary);
      opacity: 1;
    }
    .novo-template-page .use-btn {
      background: var(--c-soft);
      color: var(--c-primary);
      border: 1px solid var(--c-border);
      opacity: 0;
      transition: all 0.2s;
    }
    .novo-template-page .card:hover .use-btn { opacity: 1; }
    .novo-template-page .preview-btn {
      background: transparent;
      color: var(--c-muted);
      border: 1px solid var(--c-border);
      transition: all 0.2s;
    }
    .novo-template-page .preview-btn:hover { color: var(--c-primary); border-color: var(--c-primary); }
    .novo-template-page .tag {
      background: var(--c-soft);
      color: var(--c-muted);
      border: 1px solid var(--c-border);
      font-size: 10px;
      letter-spacing: 0.05em;
    }
    .novo-template-page .search-input {
      background: var(--c-surface);
      border: 1px solid var(--c-border);
      color: var(--c-text);
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .novo-template-page .search-input:focus {
      outline: none;
      border-color: var(--c-primary);
      box-shadow: 0 0 0 3px var(--c-focus);
    }
    .novo-template-page .search-input::placeholder { color: var(--c-muted); }
    .novo-template-page .filter-btn {
      background: var(--c-surface);
      border: 1px solid var(--c-border);
      color: var(--c-muted);
      transition: all 0.2s;
    }
    .novo-template-page .filter-btn:hover,
    .novo-template-page .filter-btn.active {
      border-color: var(--c-primary);
      color: var(--c-primary);
      background: var(--c-soft);
    }
    .novo-template-page .section-label {
      color: var(--c-muted);
      font-size: 11px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .novo-template-page .card { animation: fadeUp 0.3s ease both; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .novo-template-page .card:nth-child(1) { animation-delay: 0.05s; }
    .novo-template-page .card:nth-child(2) { animation-delay: 0.10s; }
    .novo-template-page .card:nth-child(3) { animation-delay: 0.15s; }
    .novo-template-page .card:nth-child(4) { animation-delay: 0.20s; }
    .novo-template-page .card:nth-child(5) { animation-delay: 0.25s; }
    .novo-template-page .card:nth-child(6) { animation-delay: 0.30s; }
    .novo-template-page .card:nth-child(7) { animation-delay: 0.35s; }
    .novo-template-page .card:nth-child(8) { animation-delay: 0.40s; }
    .novo-template-page .modal-overlay {
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(4px);
    }
    .novo-template-page .modal-box {
      background: var(--c-surface);
      border: 1px solid var(--c-border);
      max-width: min(42rem, 95vw);
      max-height: 90vh;
      display: flex;
      flex-direction: column;
    }
    .novo-template-page .modal-preview-body {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
    }
    .novo-template-page .modal-cancel-btn {
      background: transparent;
      color: var(--c-muted);
      border: 1px solid var(--c-border);
      transition: color 0.2s, border-color 0.2s, background 0.2s;
    }
    .novo-template-page .modal-cancel-btn:hover {
      color: var(--c-primary);
      border-color: var(--c-primary);
      background: var(--c-soft);
    }
    .novo-template-page .preview-field-label {
      display: block;
      font-size: 0.75rem;
      font-weight: 500;
      color: var(--c-muted);
      margin-bottom: 0.25rem;
    }
    .novo-template-page .preview-field-box {
      background: var(--c-soft);
      border: 1px solid var(--c-border);
      border-radius: 0.5rem;
      color: var(--c-muted);
      font-size: 0.8125rem;
      padding: 0.5rem 0.75rem;
      min-height: 2.25rem;
    }
    .novo-template-page .preview-field-box.preview-textarea { min-height: 4rem; }
    .novo-template-page .preview-field-box.preview-signature { min-height: 5rem; display: flex; align-items: center; justify-content: center; }
    .novo-template-page .header-icon-wrap {
      border-color: var(--c-primary);
      color: var(--c-primary);
    }
    .novo-template-page .page-title-text { color: var(--c-text); }
    .novo-template-page .page-subtitle { color: var(--c-muted); }
    .novo-template-page .search-icon { color: var(--c-muted); }
    .novo-template-page .filter-count { color: var(--c-muted); }
    .novo-template-page .card-title { color: var(--c-text); }
    .novo-template-page .card-desc { color: var(--c-muted); }
    .novo-template-page .blank-cta {
      border-color: var(--c-border);
      color: var(--c-text);
    }
    .novo-template-page .blank-cta:hover { border-color: var(--c-primary); }
    .novo-template-page .blank-cta .blank-cta-title { color: var(--c-muted); }
    .novo-template-page .blank-cta:hover .blank-cta-title { color: var(--c-text); }
    .novo-template-page .blank-cta .blank-cta-sub { color: var(--c-muted); }
    .novo-template-page .blank-cta .blank-cta-icon-wrap {
      border-color: var(--c-border);
      color: var(--c-muted);
    }
    .novo-template-page .blank-cta:hover .blank-cta-icon-wrap {
      border-color: var(--c-primary);
      color: var(--c-primary);
    }
    .novo-template-page .modal-title { color: var(--c-text); }
    .novo-template-page .modal-close-btn { color: var(--c-muted); }
    .novo-template-page .modal-close-btn:hover { color: var(--c-text); }
    .novo-template-page .modal-desc { color: var(--c-muted); }
    .novo-template-page .modal-use-btn-primary {
      background: var(--c-primary);
      color: #fff;
      border: 1px solid var(--c-primary);
      opacity: 1;
      transition: background 0.2s, border-color 0.2s, filter 0.2s;
    }
    .novo-template-page .modal-use-btn-primary:hover {
      filter: brightness(1.08);
    }
    .novo-template-page .empty-state-text { color: var(--c-muted); }
    .novo-template-page .card-icon {
      background: var(--c-soft);
      color: var(--c-muted);
    }
  </style>

  <div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
      <div class="header-icon-wrap w-8 h-8 rounded-full border flex items-center justify-center">
        <span class="material-symbols-outlined" style="font-size:16px">add_circle</span>
      </div>
      <h1 class="page-title-text text-xl font-semibold tracking-tight">Novo template</h1>
    </div>
    <p class="page-subtitle text-sm mb-7 ml-11">Escolha um modelo para começar ou crie um template em branco.</p>

    {{-- Busca (linha inteira) --}}
    <div class="mb-4">
      <div class="relative w-full">
        <span class="material-symbols-outlined search-icon absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="font-size:18px">search</span>
        <input id="searchInput" class="search-input w-full pl-10 pr-4 py-2.5 rounded-lg text-sm min-w-0" placeholder="Buscar template..." />
      </div>
    </div>

    {{-- Filtros (linha separada) --}}
    <div class="flex gap-2 flex-wrap mb-6">
      <button type="button" class="filter-btn active text-xs px-3 py-2 rounded-lg" data-filter="todos">
        Todos <span class="mono ml-1 filter-count" id="count-todos">{{ $allTemplates->count() }}</span>
      </button>
      @foreach($categoryKeys as $catKey)
        <button type="button" class="filter-btn text-xs px-3 py-2 rounded-lg" data-filter="{{ $catKey }}">
          {{ $categoryLabels[$catKey] ?? $catKey }} <span class="mono ml-1 filter-count">{{ $templatesByCategory[$catKey]->count() }}</span>
        </button>
      @endforeach
    </div>

    {{-- Section --}}
    <div class="flex items-center justify-between mb-4">
      <span class="section-label">{{ $categoryLabels['geral'] ?? 'Geral (todos os tenants)' }}</span>
      <span class="mono text-xs filter-count" id="resultCount">{{ $allTemplates->count() }} template(s)</span>
    </div>

    {{-- Grid --}}
    <div id="cardGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($allTemplates as $t)
        @php $emoji = $categoryEmoji[$t->category] ?? '📄'; @endphp
        <div class="card rounded-xl p-5 flex flex-col gap-3 cursor-pointer"
             data-name="{{ e($t->name) }}"
             data-cat="{{ e($t->category ?? '') }}"
             data-desc="{{ e($t->description ?? '') }}"
             data-fields="{{ base64_encode($t->fields->toJson()) }}">
          <div class="flex items-start">
            <div class="card-icon w-9 h-9 rounded-lg flex items-center justify-center text-lg transition-all">{{ $emoji }}</div>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="card-title text-sm font-medium">{{ $t->name }}</span>
            </div>
            <p class="card-desc text-xs leading-relaxed">{{ Str::limit($t->description, 120) ?: 'Sem descrição.' }}</p>
          </div>
          <div class="flex gap-1.5 flex-wrap">
            @if($t->category && isset($categoryLabels[$t->category]))
              <span class="tag px-2 py-0.5 rounded-full">{{ $categoryLabels[$t->category] }}</span>
            @endif
          </div>
          <div class="flex gap-2 mt-auto pt-1">
            <button type="button" class="preview-btn flex-1 text-xs py-2 rounded-lg">Pré-visualizar</button>
            <form action="{{ route('templates.store.from', $t) }}" method="POST" class="flex-1" style="margin:0">
              @csrf
              <button type="submit" class="use-btn w-full text-xs py-2 rounded-lg font-medium">Usar modelo</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Empty state --}}
    <div id="emptyState" class="hidden text-center py-16 empty-state-text">
      <div class="text-4xl mb-3">🔍</div>
      <p class="text-sm">Nenhum template encontrado.</p>
    </div>

    {{-- Blank template CTA --}}
    <a href="{{ route('templates.create.blank') }}" class="blank-cta mt-6 border border-dashed rounded-xl p-5 flex items-center justify-between transition-colors group cursor-pointer no-underline">
      <div>
        <p class="blank-cta-title text-sm font-medium transition-colors">Criar template em branco</p>
        <p class="blank-cta-sub text-xs mt-0.5">Comece do zero com total liberdade</p>
      </div>
      <div class="blank-cta-icon-wrap w-8 h-8 rounded-full border flex items-center justify-center transition-all">
        <span class="material-symbols-outlined" style="font-size:14px">add</span>
      </div>
    </a>
  </div>

  {{-- Preview Modal --}}
  <div id="previewModal" class="fixed inset-0 modal-overlay hidden items-center justify-center z-50 p-4" style="display: none;">
    <div class="modal-box rounded-2xl p-7 w-full">
      <div class="flex items-center justify-between mb-4 shrink-0">
        <h2 class="modal-title text-lg font-semibold" id="modalTitle">Template</h2>
        <button type="button" id="closePreviewBtn" class="modal-close-btn transition-colors p-1 rounded-lg hover:bg-[var(--c-soft)]">
          <span class="material-symbols-outlined" style="font-size:20px">close</span>
        </button>
      </div>
      <p class="modal-desc text-sm mb-4 shrink-0" id="modalDesc"></p>
      <div class="modal-preview-body">
        <div id="modalPreviewContent" class="space-y-4 mb-6 pr-1 min-h-[16rem] max-h-[50vh] overflow-y-auto"></div>
      </div>
      <div class="flex gap-3 shrink-0 pt-2">
        <button type="button" id="modalCancelBtn" class="modal-cancel-btn flex-1 text-sm py-2.5 rounded-lg font-medium">Cancelar</button>
        <form id="modalUseForm" action="#" method="POST" class="flex-1" style="margin:0">
          @csrf
          <button type="submit" class="modal-use-btn-primary use-btn w-full text-sm py-2.5 rounded-lg font-medium">Usar este modelo</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const searchInput = document.getElementById('searchInput');
  const cardGrid = document.getElementById('cardGrid');
  const resultCount = document.getElementById('resultCount');
  const emptyState = document.getElementById('emptyState');
  const previewModal = document.getElementById('previewModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalDesc = document.getElementById('modalDesc');
  const modalUseForm = document.getElementById('modalUseForm');
  const modalPreviewContent = document.getElementById('modalPreviewContent');
  let activeFilter = 'todos';

  function getOptionsList(field) {
    var opts = field.options_json;
    if (opts && opts.options && Array.isArray(opts.options)) return opts.options;
    if (Array.isArray(opts)) return opts;
    return [];
  }

  function renderPreviewFields(fields) {
    if (!modalPreviewContent) return;
    modalPreviewContent.innerHTML = '';
    if (!fields || !fields.length) {
      var empty = document.createElement('p');
      empty.className = 'text-sm text-slate-500';
      empty.textContent = 'Este modelo não possui campos definidos.';
      modalPreviewContent.appendChild(empty);
      return;
    }
    fields.forEach(function(f) {
      var wrap = document.createElement('div');
      var label = document.createElement('label');
      label.className = 'preview-field-label';
      label.textContent = f.label + (f.required ? ' *' : '');
      wrap.appendChild(label);
      var box = document.createElement('div');
      box.className = 'preview-field-box';
      var type = (f.type || 'text').toLowerCase();
      if (type === 'textarea') {
        box.classList.add('preview-textarea');
        box.textContent = '...';
      } else if (type === 'select' || type === 'radio') {
        var opts = getOptionsList(f);
        box.textContent = opts.length ? opts.join(' · ') : 'Selecione...';
      } else if (type === 'checkbox') {
        box.textContent = '☐';
      } else if (type === 'file') {
        box.textContent = 'Escolher arquivo';
      } else if (type === 'signature') {
        box.classList.add('preview-signature');
        box.textContent = 'Assinatura';
      } else {
        box.textContent = type === 'date' ? 'dd/mm/aaaa' : (type === 'number' ? '0' : '...');
      }
      wrap.appendChild(box);
      modalPreviewContent.appendChild(wrap);
    });
  }

  function setFilter(cat, btn) {
    activeFilter = cat;
    document.querySelectorAll('.novo-template-page .filter-btn').forEach(function(b) {
      b.classList.toggle('active', b.getAttribute('data-filter') === cat);
    });
    filterCards();
  }

  function filterCards() {
    const query = (searchInput && searchInput.value) ? searchInput.value.toLowerCase() : '';
    const cards = cardGrid ? cardGrid.querySelectorAll('.card') : [];
    let visible = 0;
    cards.forEach(function(card) {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const cat = card.getAttribute('data-cat') || '';
      const matchSearch = !query || name.includes(query);
      const matchFilter = activeFilter === 'todos' || cat === activeFilter;
      if (matchSearch && matchFilter) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });
    if (resultCount) resultCount.textContent = visible + ' template' + (visible !== 1 ? 's' : '');
    if (emptyState) emptyState.classList.toggle('hidden', visible > 0);
  }

  function openPreview(btn) {
    const card = btn.closest('.card');
    if (!card) return;
    const name = card.getAttribute('data-name') || 'Template';
    const desc = card.getAttribute('data-desc') || '';
    const form = card.querySelector('form');
    const action = form ? form.getAttribute('action') : '#';
    if (modalTitle) modalTitle.textContent = name;
    if (modalDesc) modalDesc.textContent = desc;
    if (modalUseForm) { modalUseForm.action = action; }
    var fieldsEnc = card.getAttribute('data-fields');
    var fields = [];
    if (fieldsEnc) {
      try {
        fields = JSON.parse(atob(fieldsEnc));
      } catch (e) {}
    }
    renderPreviewFields(fields);
    if (previewModal) { previewModal.classList.remove('hidden'); previewModal.style.display = 'flex'; }
  }

  function closePreview() {
    if (previewModal) { previewModal.classList.add('hidden'); previewModal.style.display = 'none'; }
  }

  if (searchInput) searchInput.addEventListener('input', filterCards);

  document.querySelectorAll('.novo-template-page .filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      setFilter(btn.getAttribute('data-filter'), btn);
    });
  });

  document.querySelectorAll('.novo-template-page .preview-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); openPreview(btn); });
  });

  if (document.getElementById('closePreviewBtn')) document.getElementById('closePreviewBtn').addEventListener('click', closePreview);
  if (document.getElementById('modalCancelBtn')) document.getElementById('modalCancelBtn').addEventListener('click', closePreview);

  if (previewModal) {
    previewModal.addEventListener('click', function(e) {
      if (e.target === this) closePreview();
    });
  }
})();
</script>
@endsection
