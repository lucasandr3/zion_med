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
            <div class="link-bio-tab" data-tab="aparencia" onclick="switchTab(this,'aparencia')"><span class="material-symbols-outlined">palette</span> Aparência</div>
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

        {{-- Tab Aparência --}}
        <div id="tab-aparencia" class="hidden">
            <form action="{{ route('link-bio.aparencia.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                @csrf @method('PUT')

                {{-- Tema de cor --}}
                <div class="card p-5">
                    <p class="text-xs font-bold uppercase tracking-wider mb-4" style="color:var(--c-muted)">Tema de cor da página</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:8px" id="aparencia-theme-grid">
                        @php $currentPublicTheme = old('public_theme', $clinic->public_theme ?? ''); @endphp
                        {{-- Opção padrão --}}
                        <label class="theme-card {{ $currentPublicTheme === '' ? 'selected' : '' }}" style="border:2px solid var(--c-border);border-radius:10px;padding:10px;cursor:pointer;position:relative;background:var(--c-surface){{ $currentPublicTheme === '' ? ';border-color:var(--c-primary)' : '' }}">
                            <input type="radio" name="public_theme" value="" {{ $currentPublicTheme === '' ? 'checked' : '' }} class="sr-only">
                            <div style="height:28px;border-radius:5px;margin-bottom:7px;background:#f0ede8;display:flex;gap:2px;align-items:flex-end;padding:4px">
                                <div style="background:#1a1a2e;border-radius:2px;width:5px;height:10px"></div>
                                <div style="background:#1a1a2e;border-radius:2px;width:5px;height:16px"></div>
                                <div style="background:#1a1a2e;border-radius:2px;width:5px;height:22px"></div>
                            </div>
                            <div style="width:20px;height:20px;border-radius:50%;background:#1a1a2e;margin-bottom:5px"></div>
                            <div style="font-size:10.5px;font-weight:500;color:var(--c-text)">Padrão</div>
                            @if($currentPublicTheme === '')<span style="position:absolute;top:4px;right:6px;font-size:10px;color:var(--c-primary);font-weight:700">✓</span>@endif
                        </label>
                        @foreach($availableThemes as $key => $meta)
                            @php $p = $meta['primary']; @endphp
                            <label class="theme-card {{ $currentPublicTheme === $key ? 'selected' : '' }}" style="border:2px solid var(--c-border);border-radius:10px;padding:10px;cursor:pointer;position:relative;background:var(--c-surface){{ $currentPublicTheme === $key ? ';border-color:var(--c-primary)' : '' }}">
                                <input type="radio" name="public_theme" value="{{ $key }}" {{ $currentPublicTheme === $key ? 'checked' : '' }} class="sr-only">
                                <div style="height:28px;border-radius:5px;margin-bottom:7px;background:{{ $p }}22;display:flex;gap:2px;align-items:flex-end;padding:4px">
                                    <div style="background:{{ $p }};border-radius:2px;width:5px;height:10px"></div>
                                    <div style="background:{{ $p }};border-radius:2px;width:5px;height:16px"></div>
                                    <div style="background:{{ $p }};border-radius:2px;width:5px;height:22px"></div>
                                </div>
                                <div style="width:20px;height:20px;border-radius:50%;background:{{ $p }};margin-bottom:5px"></div>
                                <div style="font-size:10.5px;font-weight:500;color:var(--c-text)">{{ $meta['label'] }}</div>
                                @if($currentPublicTheme === $key)<span style="position:absolute;top:4px;right:6px;font-size:10px;color:var(--c-primary);font-weight:700">✓</span>@endif
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] mt-3" style="color:var(--c-muted)">Afeta o logo, botões e indicador de horário aberto. Independente do tema do sistema.</p>
                </div>

                {{-- Banner de capa --}}
                <div class="card p-5">
                    <p class="text-xs font-bold uppercase tracking-wider mb-4" style="color:var(--c-muted)">Banner de capa</p>

                    @if($clinic->cover_image_url)
                        <div class="mb-4">
                            <p class="text-[11px] mb-2" style="color:var(--c-muted)">Imagem atual:</p>
                            <img src="{{ $clinic->cover_image_url }}" alt="Capa" style="width:100%;max-height:80px;border-radius:8px;object-fit:cover;border:1px solid var(--c-border)">
                        </div>
                    @elseif($clinic->cover_color)
                        <div class="mb-4">
                            <p class="text-[11px] mb-2" style="color:var(--c-muted)">Cor atual:</p>
                            <div style="width:100%;height:48px;border-radius:8px;background:{{ $clinic->cover_color }};border:1px solid var(--c-border)"></div>
                        </div>
                    @endif

                    {{-- Tipo de capa --}}
                    <div class="flex gap-2 mb-4" id="ap-cover-type">
                        <button type="button" class="ap-cover-btn {{ !$clinic->cover_color ? 'ap-active' : '' }}" data-type="image"
                            style="flex:1;padding:9px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--c-border);background:{{ !$clinic->cover_color ? 'var(--c-soft)' : 'transparent' }};color:var(--c-text);transition:all .15s">
                            <span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;margin-right:3px">image</span>Imagem
                        </button>
                        <button type="button" class="ap-cover-btn {{ $clinic->cover_color ? 'ap-active' : '' }}" data-type="color"
                            style="flex:1;padding:9px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--c-border);background:{{ $clinic->cover_color ? 'var(--c-soft)' : 'transparent' }};color:var(--c-text);transition:all .15s">
                            <span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;margin-right:3px">format_paint</span>Cor sólida
                        </button>
                        <button type="button" class="ap-cover-btn {{ !$clinic->cover_color && !$clinic->cover_image_path ? 'ap-active' : '' }}" data-type="none"
                            style="flex:1;padding:9px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--c-border);background:{{ !$clinic->cover_color && !$clinic->cover_image_path ? 'var(--c-soft)' : 'transparent' }};color:var(--c-text);transition:all .15s">
                            <span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;margin-right:3px">block</span>Nenhum
                        </button>
                    </div>

                    <input type="hidden" id="ap-cover-clear-flag" name="_cover_color_clear" value="{{ $clinic->cover_color ? '0' : '1' }}">
                    <input type="hidden" id="ap-cover-none-flag" name="_cover_none" value="0">

                    <div id="ap-panel-image" style="{{ $clinic->cover_color ? 'display:none' : '' }}">
                        <div style="border:2px dashed var(--c-border);border-radius:10px;padding:20px;text-align:center;cursor:pointer" onclick="document.getElementById('ap-cover-input').click()">
                            <span class="material-symbols-outlined" style="font-size:28px;color:var(--c-muted)">upload_file</span>
                            <p class="text-sm font-medium mt-1" style="color:var(--c-text)">Arraste ou <span style="color:var(--c-primary)">clique para escolher</span></p>
                            <p class="text-[11px] mt-0.5" style="color:var(--c-muted)">PNG, JPG • Recomendado 1200×400px • Máx 3MB</p>
                        </div>
                        <input type="file" name="cover_image" id="ap-cover-input" accept="image/*" class="sr-only" onchange="showApCoverPreview(this)">
                        <div id="ap-cover-preview" class="mt-2 hidden">
                            <img id="ap-cover-preview-img" src="" alt="" style="width:100%;max-height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--c-border)">
                        </div>
                    </div>

                    <div id="ap-panel-color" style="{{ $clinic->cover_color ? '' : 'display:none' }}">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <input type="color" id="ap-color-picker" value="{{ old('cover_color', $clinic->cover_color ?? '#1a1a2e') }}"
                                style="width:44px;height:38px;border:1px solid var(--c-border);border-radius:8px;padding:3px;cursor:pointer;background:var(--c-surface)">
                            <input type="text" name="cover_color" id="ap-color-text" value="{{ old('cover_color', $clinic->cover_color ?? '#1a1a2e') }}"
                                class="form-input" placeholder="#1a1a2e" maxlength="7" pattern="^#[0-9a-fA-F]{6}$"
                                style="flex:1;min-width:100px">
                            <div id="ap-color-preview" style="width:38px;height:38px;border-radius:8px;border:1px solid var(--c-border);background:{{ $clinic->cover_color ?? '#1a1a2e' }};flex-shrink:0"></div>
                        </div>
                        <div class="mt-2" style="font-size:11px;color:var(--c-muted)">Sugestões:
                            @foreach(['#1a1a2e','#1e40af','#4f46e5','#10b981','#f43f5e','#f59e0b','#8b5cf6','#14b8a6','#475569'] as $sc)
                                <button type="button" onclick="setApColor('{{ $sc }}')" title="{{ $sc }}"
                                    style="display:inline-block;width:15px;height:15px;border-radius:50%;background:{{ $sc }};border:1px solid rgba(0,0,0,.12);margin-left:4px;cursor:pointer;vertical-align:middle"></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Informações da página --}}
                <div class="card p-5">
                    <p class="text-xs font-bold uppercase tracking-wider mb-4" style="color:var(--c-muted)">Informações exibidas na página</p>
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="form-label">Descrição / Slogan</label>
                            <input type="text" name="short_description" value="{{ old('short_description', $clinic->short_description) }}"
                                class="form-input" placeholder="Ex: Cuidando da sua saúde com excelência" maxlength="200">
                        </div>
                        <div>
                            <label class="form-label">Especialidades</label>
                            <input type="text" name="specialties" value="{{ old('specialties', $clinic->specialties) }}"
                                class="form-input" placeholder="Clínica geral, Pediatria, Dermatologia (vírgula para separar)">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Ano de fundação</label>
                                <input type="number" name="founded_year" value="{{ old('founded_year', $clinic->founded_year) }}"
                                    class="form-input" placeholder="2010" min="1900" max="{{ date('Y') }}">
                            </div>
                            <div>
                                <label class="form-label">E-mail de contato público</label>
                                <input type="email" name="contact_email" value="{{ old('contact_email', $clinic->contact_email) }}"
                                    class="form-input" placeholder="contato@empresa.com">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Link do Google Maps</label>
                            <input type="url" name="maps_url" value="{{ old('maps_url', $clinic->maps_url) }}"
                                class="form-input" placeholder="https://maps.google.com/...">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="submit" class="btn-primary px-5 py-2 text-sm inline-flex items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size:16px">save</span>
                        Salvar aparência
                    </button>
                </div>
            </form>
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
    ['links','forms','stats','aparencia'].forEach(function(name) {
        var box = document.getElementById('tab-' + name);
        if (!box) return;
        if (name === tab) {
            box.classList.remove('hidden');
            box.classList.add('flex');
            if (name === 'stats' || name === 'aparencia') { box.classList.remove('flex'); box.classList.add('block'); }
        } else {
            box.classList.add('hidden');
            box.classList.remove('flex', 'block');
        }
    });
    // Recarregar iframe da prévia ao trocar para aparência
    if (tab === 'aparencia') {
        var fr = document.querySelector('.link-bio-phone-screen');
        if (fr) fr.src = fr.src;
    }
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
// ── Aba Aparência: seletor de tema ──────────────────────────────────────
document.querySelectorAll('#aparencia-theme-grid .theme-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('#aparencia-theme-grid .theme-card').forEach(function(c) {
            c.style.borderColor = 'var(--c-border)';
            var ck = c.querySelector('.sr-only + span, span[style*="position:absolute"]');
            if (ck) ck.remove();
        });
        this.style.borderColor = 'var(--c-primary)';
    });
});

// ── Aba Aparência: seletor tipo de capa ─────────────────────────────────
function setApCoverType(type) {
    document.querySelectorAll('.ap-cover-btn').forEach(function(btn) {
        var isActive = btn.getAttribute('data-type') === type;
        btn.style.background = isActive ? 'var(--c-soft)' : 'transparent';
        btn.style.borderColor = isActive ? 'var(--c-primary)' : 'var(--c-border)';
        btn.style.color = isActive ? 'var(--c-primary)' : 'var(--c-text)';
    });
    var panelImage = document.getElementById('ap-panel-image');
    var panelColor = document.getElementById('ap-panel-color');
    var clearFlag  = document.getElementById('ap-cover-clear-flag');
    var noneFlag   = document.getElementById('ap-cover-none-flag');
    if (panelImage) panelImage.style.display = type === 'image' ? '' : 'none';
    if (panelColor) panelColor.style.display = type === 'color' ? '' : 'none';
    if (clearFlag)  clearFlag.value  = type === 'color' ? '0' : '1';
    if (noneFlag)   noneFlag.value   = type === 'none'  ? '1' : '0';
}
document.querySelectorAll('.ap-cover-btn').forEach(function(btn) {
    btn.addEventListener('click', function() { setApCoverType(this.getAttribute('data-type')); });
});

// ── Aba Aparência: color picker sincronizado ─────────────────────────────
function setApColor(val) {
    var picker  = document.getElementById('ap-color-picker');
    var text    = document.getElementById('ap-color-text');
    var preview = document.getElementById('ap-color-preview');
    if (picker)  picker.value  = val;
    if (text)    text.value    = val;
    if (preview) preview.style.background = val;
}
(function() {
    var picker  = document.getElementById('ap-color-picker');
    var text    = document.getElementById('ap-color-text');
    var preview = document.getElementById('ap-color-preview');
    if (picker) picker.addEventListener('input', function() { setApColor(this.value); });
    if (text) {
        text.addEventListener('input', function() {
            if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
                if (picker) picker.value = this.value;
                if (preview) preview.style.background = this.value;
            }
        });
    }
})();

// ── Aba Aparência: preview upload capa ──────────────────────────────────
function showApCoverPreview(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById('ap-cover-preview-img');
        var box = document.getElementById('ap-cover-preview');
        if (img) img.src = e.target.result;
        if (box) box.classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Aparência: recarregar prévia após salvar ─────────────────────────────
(function() {
    @if(session('success') && request()->routeIs('link-bio.index'))
    var apTab = document.querySelector('[data-tab="aparencia"]');
    if (apTab) switchTab(apTab, 'aparencia');
    @endif
})();
</script>
@endsection
