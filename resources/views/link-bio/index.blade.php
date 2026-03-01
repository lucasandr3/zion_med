@extends('layouts.app')

@section('title', 'Link Bio')

@section('content')
<style>
  .link-bio-metric {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 0.625rem;
    padding: 0.875rem 1rem;
    flex: 1;
  }
  .link-bio-metric-value { font-size: 1.5rem; font-weight: 700; }
  .link-bio-metric-label { font-size: 0.6875rem; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.05em; }
  .link-bio-tab { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 0.375rem; font-size: 0.8125rem; font-weight: 500; cursor: pointer; color: var(--c-muted); transition: all 0.15s; }
  .link-bio-tab.active { background: var(--c-primary); color: #fff; }
  .link-bio-tab:not(.active):hover { color: var(--c-text); }
  .link-bio-tab .material-symbols-outlined { font-size: 1.125rem; }
  .link-bio-link-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.875rem; background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 0.625rem; transition: border-color 0.15s; cursor: grab; }
  .link-bio-link-row:hover { border-color: var(--c-primary); }
  .link-bio-form-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 0.875rem; background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 0.625rem; transition: border-color 0.15s; }
  .link-bio-form-row:hover { border-color: var(--c-border); }
  .link-bio-phone { width: 320px; height: 600px; border-radius: 1.5rem; border: 2px solid var(--c-border); background: var(--c-bg); overflow: hidden; flex-shrink: 0; box-shadow: 0 8px 40px rgba(0,0,0,0.15); }
  .link-bio-phone-screen { width: 100%; height: 100%; overflow-y: auto; scrollbar-width: none; display: block; }
  .link-bio-phone-screen::-webkit-scrollbar { display: none; }
  .link-bio-bar-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; }
  .link-bio-bar { background: var(--c-primary); border-radius: 3px 3px 0 0; width: 100%; transition: height 0.3s; }
  .link-bio-bar-label { font-size: 0.5625rem; color: var(--c-muted); }
  .link-bio-badge { font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.04em; padding: 2px 8px; border-radius: 99px; text-transform: uppercase; }
</style>

<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-[0.6875rem] uppercase tracking-wider mb-1" style="color:var(--c-muted)">Link Bio</p>
            <h1 class="text-xl font-bold leading-tight" style="color:var(--c-text)">{{ $clinic->name }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="link-bio-badge" style="background:color-mix(in srgb,var(--c-primary) 15%,transparent);color:var(--c-primary)">● Publicado</span>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm transition-colors" style="background:var(--c-soft);color:var(--c-muted);border:1px solid var(--c-border)">
                <span class="material-symbols-outlined" style="font-size:16px">open_in_new</span>
                Ver página
            </a>
            <button type="button" onclick="copyLinkBio()" class="btn-primary inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm">
                <span class="material-symbols-outlined" style="font-size:16px">content_copy</span>
                <span id="copy-label">Copiar link</span>
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-2"
         style="background:color-mix(in srgb,var(--c-primary) 10%,transparent);color:var(--c-primary);border:1px solid color-mix(in srgb,var(--c-primary) 25%,transparent)">
        <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
        {{ session('success') }}
    </div>
@endif

{{-- Barra do link --}}
<div class="flex flex-wrap items-center gap-2 mb-6">
    <input type="text" readonly value="{{ $publicUrl }}" id="bio-url-input"
           class="form-input flex-1 min-w-0 max-w-md text-sm cursor-pointer" onclick="this.select()">
    <button type="button" onclick="copyLinkBio(); this.textContent='✓ Copiado!'; setTimeout(()=>this.textContent='Copiar',2000)"
            class="px-3 py-2 rounded-lg text-sm transition-colors whitespace-nowrap"
            style="background:var(--c-soft);color:var(--c-muted);border:1px solid var(--c-border)">Copiar</button>
</div>

{{-- 4 métricas --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="link-bio-metric">
        <div class="link-bio-metric-label mb-1.5">Visitas hoje</div>
        <div class="link-bio-metric-value" style="color:var(--c-primary)">{{ number_format($visitasHoje) }}</div>
    </div>
    <div class="link-bio-metric">
        <div class="link-bio-metric-label mb-1.5">Cliques totais</div>
        <div class="link-bio-metric-value">{{ number_format($totalClicksLast30) }}</div>
        <div class="text-[0.6875rem] mt-0.5" style="color:var(--c-muted)">últimos 30 dias</div>
    </div>
    <div class="link-bio-metric">
        <div class="link-bio-metric-label mb-1.5">Taxa de clique</div>
        <div class="link-bio-metric-value" style="color:var(--c-primary)">{{ $taxaClique }}%</div>
        <div class="text-[0.6875rem] mt-0.5" style="color:var(--c-muted)">CTR</div>
    </div>
    <div class="link-bio-metric">
        <div class="link-bio-metric-label mb-1.5">Formulários</div>
        <div class="link-bio-metric-value">{{ $formulariosTotal }}</div>
        <div class="text-[0.6875rem] mt-0.5" style="color:var(--c-muted)">{{ $formulariosAtivos }} ativos, {{ $formulariosDraft }} rascunho</div>
    </div>
</div>

{{-- Conteúdo principal: abas + preview --}}
<div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">
    <div>
        {{-- Tabs --}}
        <div class="flex gap-1 mb-5 p-1 rounded-lg w-fit" style="background:var(--c-surface);border:1px solid var(--c-border)">
            <div class="link-bio-tab active" data-tab="links" onclick="switchTab(this,'links')"><span class="material-symbols-outlined">link</span> Links</div>
            <div class="link-bio-tab" data-tab="forms" onclick="switchTab(this,'forms')"><span class="material-symbols-outlined">description</span> Formulários</div>
            <div class="link-bio-tab" data-tab="stats" onclick="switchTab(this,'stats')"><span class="material-symbols-outlined">bar_chart</span> Estatísticas</div>
        </div>

        {{-- Tab Links --}}
        <div id="tab-links" class="flex flex-col gap-2.5">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-1">
                <p class="text-xs" style="color:var(--c-muted)">Arraste para reordenar · {{ $bioLinks->count() }} links</p>
                <button type="button" onclick="toggleAddForm()" id="btn-add" class="btn-primary text-sm px-3 py-1.5 inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined" style="font-size:16px">add</span>
                    Adicionar link
                </button>
            </div>

            <div id="add-form" style="display:none;margin-bottom:1rem">
                <form action="{{ route('link-bio.links.store') }}" method="POST" class="p-4 rounded-xl" style="background:var(--c-soft);border:1px solid var(--c-border)">
                    @csrf
                    <p class="text-sm font-semibold mb-3" style="color:var(--c-text)">Novo link</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="form-label">Título <span style="color:var(--c-primary)">*</span></label>
                            <input type="text" name="label" required maxlength="80" placeholder="Ex: WhatsApp, Instagram…" class="form-input" value="{{ old('label') }}">
                            @error('label')<p class="text-xs mt-1" style="color:#f87171">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">URL <span style="color:var(--c-primary)">*</span></label>
                            <input type="url" name="url" required maxlength="500" placeholder="https://…" class="form-input" value="{{ old('url') }}">
                            @error('url')<p class="text-xs mt-1" style="color:#f87171">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Ícone</label>
                            <select name="icon" class="form-select">
                                @foreach($availableIcons as $value => $label)
                                    <option value="{{ $value }}" {{ old('icon', 'link') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary text-sm">Salvar</button>
                        <button type="button" onclick="toggleAddForm()" class="text-sm px-3 py-1.5 rounded" style="color:var(--c-muted)">Cancelar</button>
                    </div>
                </form>
            </div>

            @if($bioLinks->isNotEmpty())
                <ul id="links-list" class="flex flex-col gap-2.5">
                    @foreach($bioLinks as $lnk)
                        <li id="link-item-{{ $lnk->id }}" data-id="{{ $lnk->id }}" class="link-bio-link-row flex-wrap">
                            <span class="material-symbols-outlined drag-handle shrink-0" style="font-size:20px;color:var(--c-muted);cursor:grab" data-tooltip="Arrastar" aria-label="Arrastar para reordenar">drag_indicator</span>
                            <span class="material-symbols-outlined shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" style="font-size:18px;background:color-mix(in srgb,var(--c-primary) 12%,transparent);color:var(--c-primary)">{{ $lnk->icon }}</span>
                            <div class="flex-1 min-w-0 view-mode">
                                <div class="text-sm font-semibold truncate" style="color:var(--c-text)">{{ $lnk->label }}</div>
                                <div class="text-xs truncate mt-0.5" style="color:var(--c-muted)">{{ Str::limit($lnk->url, 40) }}</div>
                            </div>
                            <form method="POST" action="{{ route('link-bio.links.update', $lnk) }}" class="flex-1 min-w-0 edit-mode hidden">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                                    <input type="text" name="label" required maxlength="80" value="{{ $lnk->label }}" class="form-input text-sm" placeholder="Título">
                                    <input type="url" name="url" required maxlength="500" value="{{ $lnk->url }}" class="form-input text-sm" placeholder="URL">
                                    <select name="icon" class="form-select text-sm">
                                        @foreach($availableIcons as $value => $label)
                                            <option value="{{ $value }}" {{ $lnk->icon === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary text-xs px-2 py-1">Salvar</button>
                                    <button type="button" onclick="cancelEdit({{ $lnk->id }})" class="text-xs px-2 py-1 rounded" style="color:var(--c-muted)">Cancelar</button>
                                </div>
                            </form>
                            <div class="flex items-center gap-2 shrink-0 actions-mode">
                                <span class="text-xs" style="color:var(--c-primary)">{{ number_format($lnk->total_clicks ?? 0) }} cliques</span>
                                <span class="link-bio-badge" style="background:color-mix(in srgb,var(--c-primary) 12%,transparent);color:var(--c-primary)">ativo</span>
                                <button type="button" onclick="startEdit({{ $lnk->id }})" class="px-2.5 py-1 rounded-lg text-xs transition-colors" style="background:var(--c-soft);color:var(--c-muted);border:1px solid var(--c-border)">Editar</button>
                                <a href="{{ $lnk->url }}" target="_blank" rel="noopener" class="p-1.5 rounded-lg" style="color:var(--c-muted)" data-tooltip="Abrir" aria-label="Abrir link"><span class="material-symbols-outlined" style="font-size:17px">open_in_new</span></a>
                                <form method="POST" action="{{ route('link-bio.links.destroy', $lnk) }}" onsubmit="return confirm('Remover este link?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg" style="color:var(--c-muted)" data-tooltip="Remover" aria-label="Remover link"><span class="material-symbols-outlined" style="font-size:17px">delete</span></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="py-10 text-center rounded-xl" style="background:var(--c-soft);border:1px dashed var(--c-border)">
                    <span class="material-symbols-outlined block mb-2" style="font-size:40px;color:var(--c-muted)">add_link</span>
                    <p class="text-sm font-medium" style="color:var(--c-text)">Nenhum link cadastrado</p>
                    <p class="text-xs mt-1" style="color:var(--c-muted)">Clique em "Adicionar link" para começar.</p>
                </div>
            @endif
        </div>

        {{-- Tab Formulários --}}
        <div id="tab-forms" class="hidden flex-col gap-2.5">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-1">
                <p class="text-xs" style="color:var(--c-muted)">Formulários vinculados à página</p>
                <a href="{{ route('templates.create') }}" class="btn-primary text-sm px-3 py-1.5 inline-flex items-center gap-1.5">+ Novo formulário</a>
            </div>
            @foreach($formTemplatesForTab as $t)
                <div class="link-bio-form-row">
                    <div class="w-2 h-2 rounded-full shrink-0" style="background:var(--c-primary)"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold" style="color:var(--c-text)">{{ $t->name }}</div>
                        <div class="text-xs mt-0.5" style="color:var(--c-muted)">
                            @if($t->last_submission_at)
                                Última resposta: {{ $t->last_submission_at->diffForHumans() }} · <span style="color:var(--c-primary)">{{ $t->submission_count }} respostas</span>
                            @else
                                Nenhuma resposta ainda
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        @php $formUrl = route('formulario-publico.show', $t->public_token); @endphp
                        <a href="{{ route('protocolos.index') }}?template={{ $t->id }}" class="px-2.5 py-1 rounded-lg text-xs transition-colors" style="background:var(--c-soft);color:var(--c-muted);border:1px solid var(--c-border)">Ver</a>
                        <button type="button" data-copy-url="{{ $formUrl }}" onclick="copyFormLink(this)" class="px-2.5 py-1 rounded-lg text-xs transition-colors" style="background:var(--c-soft);color:var(--c-primary);border:1px solid var(--c-border)">Copiar link</button>
                    </div>
                </div>
            @endforeach
            @if($formTemplatesForTab->isEmpty())
                <div class="py-8 text-center rounded-xl" style="background:var(--c-soft);border:1px dashed var(--c-border)">
                    <span class="material-symbols-outlined block mb-2" style="font-size:32px;color:var(--c-muted)">link_off</span>
                    <p class="text-sm" style="color:var(--c-text)">Nenhum formulário com link público</p>
                    <p class="text-xs mt-1" style="color:var(--c-muted)">Em <a href="{{ route('templates.index') }}" class="underline" style="color:var(--c-primary)">Templates</a>, escolha um template → Campos → <strong>Gerar link público</strong> para que ele apareça aqui e na página do Link Bio.</p>
                </div>
            @endif
        </div>

        {{-- Tab Estatísticas --}}
        <div id="tab-stats" class="hidden">
            <div class="card p-5">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-5">
                    <h3 class="text-sm font-bold" style="color:var(--c-text)">Cliques por dia (últimos 7 dias)</h3>
                    <span class="text-xs" style="color:var(--c-muted)">Total: {{ number_format($totalClicks) }}</span>
                </div>
                @php
                    $maxClicks = max(1, collect($clicksPerDay)->max());
                    $dayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
                @endphp
                <div class="flex items-end gap-2 h-20">
                    @foreach($clicksPerDay as $date => $count)
                        @php $dow = \Carbon\Carbon::parse($date)->dayOfWeekIso; $label = $dayLabels[$dow - 1] ?? ''; @endphp
                        <div class="link-bio-bar-wrap">
                            <div class="link-bio-bar" style="height:{{ $maxClicks > 0 ? round($count / $maxClicks * 100) : 0 }}%"></div>
                            <div class="link-bio-bar-label">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-6">
                    <div>
                        <div class="text-[0.6875rem] uppercase tracking-wider mb-2" style="color:var(--c-muted)">Link mais clicado</div>
                        @if($mostClickedLink && ($mostClickedLink->total_clicks ?? 0) > 0)
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size:20px;color:var(--c-primary)">{{ $mostClickedLink->icon }}</span>
                                <div>
                                    <div class="text-sm font-semibold" style="color:var(--c-text)">{{ $mostClickedLink->label }}</div>
                                    <div class="text-xs" style="color:var(--c-primary)">{{ number_format($mostClickedLink->total_clicks) }} cliques</div>
                                </div>
                            </div>
                        @else
                            <div class="text-sm" style="color:var(--c-muted)">Ainda sem cliques</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-[0.6875rem] uppercase tracking-wider mb-2" style="color:var(--c-muted)">Dia com mais visitas</div>
                        @if($peakDayLabel)
                            <div class="text-sm font-semibold" style="color:var(--c-text)">{{ $peakDayLabel }}</div>
                            <div class="text-xs" style="color:var(--c-muted)">Dia da semana com mais acessos</div>
                        @else
                            <div class="text-sm" style="color:var(--c-muted)">Ainda sem dados</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview celular --}}
    <div class="hidden lg:flex flex-col items-center gap-3 sticky top-24">
        <p class="text-[0.6875rem] uppercase tracking-wider" style="color:var(--c-muted)">Prévia ao vivo</p>
        <div class="link-bio-phone">
            <iframe src="{{ $publicUrl }}" title="Preview Link Bio" class="link-bio-phone-screen w-full h-full border-0" loading="lazy"></iframe>
        </div>
    </div>
</div>

<script>
function copyLinkBio() {
    var url = document.getElementById('bio-url-input').value;
    navigator.clipboard.writeText(url).then(function() {
        var lbl = document.getElementById('copy-label');
        if (lbl) lbl.textContent = 'Copiado!';
        setTimeout(function(){ if (lbl) lbl.textContent = 'Copiar link'; }, 2000);
    });
}
function copyFormLink(btn) {
    var url = btn.getAttribute('data-copy-url') || '';
    navigator.clipboard.writeText(url).then(function() {
        var old = btn.innerHTML;
        btn.innerHTML = '✓ Copiado';
        setTimeout(function(){ btn.innerHTML = old; }, 1500);
    });
}
function switchTab(el, tab) {
    document.querySelectorAll('.link-bio-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    ['links','forms','stats'].forEach(function(name) {
        var box = document.getElementById('tab-' + name);
        if (!box) return;
        if (name === tab) {
            box.classList.remove('hidden');
            box.classList.add('flex');
            if (name === 'stats') box.classList.add('block');
        } else {
            box.classList.add('hidden');
            box.classList.remove('flex');
        }
    });
}
function toggleAddForm() {
    var el = document.getElementById('add-form');
    var btn = document.getElementById('btn-add');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        btn.innerHTML = '✕ Cancelar';
        var first = el.querySelector('input[name="label"]');
        if (first) first.focus();
    } else {
        el.style.display = 'none';
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">add</span> Adicionar link';
    }
}
function startEdit(id) {
    var item = document.getElementById('link-item-' + id);
    if (!item) return;
    item.querySelector('.view-mode').classList.add('hidden');
    item.querySelector('.actions-mode').classList.add('hidden');
    item.querySelector('.edit-mode').classList.remove('hidden');
    item.querySelector('.edit-mode input[name="label"]').focus();
}
function cancelEdit(id) {
    var item = document.getElementById('link-item-' + id);
    if (!item) return;
    item.querySelector('.view-mode').classList.remove('hidden');
    item.querySelector('.actions-mode').classList.remove('hidden');
    item.querySelector('.edit-mode').classList.add('hidden');
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#add-form .text-red-500, [class*="error"]') || {{ $errors->any() ? 'true' : 'false' }}) toggleAddForm();
});
(function() {
    var list = document.getElementById('links-list');
    if (!list) return;
    var dragging = null;
    list.querySelectorAll('.drag-handle').forEach(function(handle) {
        handle.addEventListener('mousedown', function(e) {
            var li = handle.closest('li');
            dragging = li;
            li.style.opacity = '0.5';
            e.preventDefault();
        });
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        var target = document.elementFromPoint(e.clientX, e.clientY);
        var targetLi = target && target.closest('#links-list li');
        if (targetLi && targetLi !== dragging) {
            var rect = targetLi.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) list.insertBefore(dragging, targetLi);
            else list.insertBefore(dragging, targetLi.nextSibling);
        }
    });
    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging.style.opacity = '';
        var ids = Array.from(list.querySelectorAll('li[data-id]')).map(function(li) { return parseInt(li.dataset.id, 10); });
        dragging = null;
        fetch('{{ route('link-bio.links.reorder') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ ids: ids })
        });
    });
})();
</script>
@endsection
