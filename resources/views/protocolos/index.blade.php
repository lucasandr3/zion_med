@extends('layouts.app')

@section('title', 'Protocolos')

@section('content')

{{-- Page Header --}}
<div class="page-header">
    <div class="page-title">
        <div class="page-title-icon">
            <span class="material-symbols-outlined">inbox</span>
        </div>
        <div>
            <h1>Protocolos</h1>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('protocolos.exportar') }}?{{ http_build_query(request()->query()) }}"
           class="btn-ghost btn-default-bg">
            <span class="material-symbols-outlined" style="font-size:16px">download</span>
            Exportar CSV
        </a>
        <a href="{{ route('protocolos.exportar-pdf') }}?{{ http_build_query(request()->query()) }}"
           class="btn-ghost btn-default-bg">
            <span class="material-symbols-outlined" style="font-size:16px">picture_as_pdf</span>
            Exportar PDF (até 50)
        </a>
    </div>
</div>

{{-- Search + Filter Bar --}}
<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap">
    <div style="position:relative;flex:1;min-width:180px;max-width:320px">
        <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--c-muted);pointer-events:none">search</span>
        <input type="text" id="busca-input"
               placeholder="Protocolo, nome ou e-mail"
               value="{{ request('busca') }}"
               class="form-input"
               style="width:100%;padding-left:2.25rem"
               autocomplete="off">
    </div>
    <button type="button" id="btn-filtros"
            style="display:inline-flex;align-items:center;gap:6px;padding:0.5rem 0.875rem;border:1px solid var(--c-border);border-radius:8px;background:var(--c-surface);color:var(--c-text);font-size:0.875rem;cursor:pointer;transition:background 0.15s;white-space:nowrap">
        <span class="material-symbols-outlined" style="font-size:18px">tune</span>
        Filtros
        @if(request()->hasAny(['template_id','status','data_inicio','data_fim']))
            <span id="filter-dot" style="width:7px;height:7px;border-radius:50%;background:var(--c-primary);display:inline-block;flex-shrink:0"></span>
        @endif
    </button>
</div>

{{-- Count --}}
<div style="font-size:0.8rem;color:var(--c-muted);margin-bottom:0.75rem">
    <span id="count-label">{{ $protocolos->total() }} {{ $protocolos->total() === 1 ? 'registro' : 'registros' }}</span>
</div>

{{-- Tabela --}}
<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Protocolo / Data</th>
                <th>Template</th>
                <th>Situação</th>
                <th>Submetente</th>
                <th>Revisado em</th>
                <th>Revisado por</th>
            </tr>
        </thead>
        <tbody id="protocolos-tbody">
            @include('protocolos._rows', ['protocolos' => $protocolos])
        </tbody>
    </table>

    {{-- Sentinel para infinite scroll --}}
    <div id="scroll-sentinel" style="height:4px"></div>

    {{-- Loading spinner --}}
    <div id="loading-more" style="display:none;text-align:center;padding:1.5rem">
        <span class="material-symbols-outlined"
              style="font-size:22px;color:var(--c-muted);display:inline-block;animation:proto-spin 0.9s linear infinite">
            autorenew
        </span>
    </div>

    @if($protocolos->isEmpty())
    <div style="text-align:center;padding:3rem 1rem">
        <span class="material-symbols-outlined" style="font-size:36px;color:var(--c-border);display:block;margin-bottom:8px">inbox</span>
        <span style="font-size:0.875rem;color:var(--c-muted)">Nenhum protocolo encontrado.</span>
    </div>
    @endif
</div>

{{-- ── Filter Drawer ── --}}
<div id="drawer-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:200;backdrop-filter:blur(2px)"
     onclick="protoCloseDrawer()"></div>

<div id="filter-drawer"
     style="position:fixed;top:0;right:0;height:100%;width:340px;max-width:92vw;
            background:var(--c-surface);border-left:1px solid var(--c-border);
            z-index:201;transform:translateX(100%);
            transition:transform 0.28s cubic-bezier(0.4,0,0.2,1);
            display:flex;flex-direction:column;
            box-shadow:-8px 0 32px rgba(0,0,0,0.14)">

    {{-- Drawer header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:1.125rem 1.25rem;border-bottom:1px solid var(--c-border);flex-shrink:0">
        <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:0.9375rem">
            <span class="material-symbols-outlined" style="font-size:20px">tune</span>
            Filtros
        </div>
        <button type="button" onclick="protoCloseDrawer()"
                data-tooltip="Fechar filtros" aria-label="Fechar filtros"
                style="padding:4px;border-radius:6px;background:transparent;border:none;
                       cursor:pointer;color:var(--c-muted);display:flex;align-items:center">
            <span class="material-symbols-outlined" style="font-size:20px">close</span>
        </button>
    </div>

    {{-- Drawer body --}}
    <form id="filter-form" method="GET" action="{{ route('protocolos.index') }}"
          style="flex:1;overflow-y:auto;padding:1.25rem;display:flex;flex-direction:column;gap:1.125rem">

        <input type="hidden" name="busca" id="drawer-busca" value="{{ request('busca') }}">

        <div>
            <label class="form-label">Template</label>
            <select name="template_id" class="form-select">
                <option value="">Todos</option>
                @foreach($templates as $t)
                    <option value="{{ $t->id }}" {{ request('template_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Situação</label>
            <select name="status" class="form-select">
                <option value="">Todas</option>
                <option value="pending"  {{ request('status')==='pending'  ? 'selected':'' }}>Pendente</option>
                <option value="approved" {{ request('status')==='approved' ? 'selected':'' }}>Aprovado</option>
                <option value="rejected" {{ request('status')==='rejected' ? 'selected':'' }}>Reprovado</option>
            </select>
        </div>

        <div>
            <label class="form-label">Data início</label>
            <input type="text" name="data_inicio" value="{{ request('data_inicio') }}" class="form-input flatpickr-date" placeholder="dd/mm/aaaa" autocomplete="off">
        </div>

        <div>
            <label class="form-label">Data fim</label>
            <input type="text" name="data_fim" value="{{ request('data_fim') }}" class="form-input flatpickr-date" placeholder="dd/mm/aaaa" autocomplete="off">
        </div>

        {{-- Footer buttons --}}
        <div style="display:flex;gap:0.5rem;margin-top:auto;padding-top:1rem;border-top:1px solid var(--c-border)">
            <a href="{{ route('protocolos.index') }}"
               style="flex:1;text-align:center;padding:0.5rem 0.75rem;border:1px solid var(--c-border);
                      border-radius:8px;font-size:0.875rem;color:var(--c-muted);
                      text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:4px">
                <span class="material-symbols-outlined" style="font-size:16px">filter_alt_off</span>
                Limpar
            </a>
            <button type="submit" class="btn-primary" style="flex:1;justify-content:center">
                <span class="material-symbols-outlined" style="font-size:16px">check</span>
                Aplicar
            </button>
        </div>
    </form>
</div>

@push('page-scripts')
<style>
@keyframes proto-spin { to { transform: rotate(360deg); } }
.protocolo-row:hover { background-color: var(--c-soft) !important; }
</style>
<script>
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    let currentPage = {{ $protocolos->currentPage() }};
    const lastPage  = {{ $protocolos->lastPage() }};
    let isLoading   = false;

    // ── Drawer ─────────────────────────────────────────────────────────────
    window.protoCloseDrawer = function () {
        document.getElementById('filter-drawer').style.transform = 'translateX(100%)';
        document.getElementById('drawer-overlay').style.display  = 'none';
        document.body.style.overflow = '';
    };

    document.getElementById('btn-filtros').addEventListener('click', function () {
        document.getElementById('filter-drawer').style.transform = 'translateX(0)';
        document.getElementById('drawer-overlay').style.display  = 'block';
        document.body.style.overflow = 'hidden';
    });

    // ── Sync busca input → drawer hidden field ──────────────────────────────
    const buscaInput  = document.getElementById('busca-input');
    const drawerBusca = document.getElementById('drawer-busca');

    buscaInput.addEventListener('input', function () {
        drawerBusca.value = this.value;
    });
    buscaInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            drawerBusca.value = this.value;
            document.getElementById('filter-form').submit();
        }
    });

    // ── Clickable rows ──────────────────────────────────────────────────────
    function bindRows(scope) {
        var rows = (scope || document).querySelectorAll('.protocolo-row:not([data-bound])');
        rows.forEach(function (row) {
            row.setAttribute('data-bound', '1');
            row.addEventListener('click', function (e) {
                if (e.target.closest('a, button')) return;
                window.location.href = this.dataset.href;
            });
        });
    }
    bindRows();

    // ── Infinite Scroll ─────────────────────────────────────────────────────
    var sentinel    = document.getElementById('scroll-sentinel');
    var loadingMore = document.getElementById('loading-more');

    if (!sentinel || lastPage <= 1) return;

    var observer = new IntersectionObserver(function (entries) {
        if (!entries[0].isIntersecting || isLoading || currentPage >= lastPage) return;

        isLoading = true;
        currentPage++;
        loadingMore.style.display = 'block';

        var params = new URLSearchParams(window.location.search);
        params.set('page', currentPage);

        fetch('{{ route('protocolos.index') }}?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(function (resp) { return resp.text(); })
        .then(function (html) {
            var tmp = document.createElement('tbody');
            tmp.innerHTML = html;
            var tbody = document.getElementById('protocolos-tbody');
            while (tmp.firstChild) tbody.appendChild(tmp.firstChild);
            bindRows();
        })
        .catch(function () { currentPage--; })
        .finally(function () {
            loadingMore.style.display = 'none';
            isLoading = false;
        });
    }, { rootMargin: '300px' });

    observer.observe(sentinel);
})();
</script>
@endpush

@endsection
