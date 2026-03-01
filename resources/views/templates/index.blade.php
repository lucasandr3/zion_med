@extends('layouts.app')

@section('title', 'Templates')

@push('styles')
<style>
.templates-group-section { transition: all 0.2s ease; }
.templates-row-hover:hover { background-color: var(--c-soft); }
.templates-chevron { transition: transform 0.2s ease; }
.templates-group-header.collapsed .templates-chevron { transform: rotate(-90deg); }
.templates-group-rows { display: table-row; }
.templates-badge-ativo {
  display: inline-flex; align-items: center; gap: 4px;
  background: color-mix(in srgb, var(--c-primary) 18%, transparent);
  color: var(--c-primary);
  font-size: 0.6875rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
}
.templates-badge-inativo {
  background: var(--c-soft); color: var(--c-muted);
  font-size: 0.6875rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
}
.templates-badge-publico {
  display: inline-flex; align-items: center; gap: 4px;
  background: color-mix(in srgb, var(--c-primary) 12%, transparent);
  color: var(--c-primary);
  font-size: 0.6875rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
}
.templates-action-btn {
  padding: 4px; border-radius: 6px; cursor: pointer;
  color: var(--c-muted); display: inline-flex; align-items: center; justify-content: center;
  text-decoration: none; border: none; background: transparent;
  transition: background 0.15s, color 0.15s;
}
.templates-action-btn:hover { background: var(--c-soft); color: var(--c-text); }
.templates-action-btn.danger:hover { color: #f87171; background: rgba(239,68,68,0.08); }
.templates-group-header {
  cursor: pointer; user-select: none;
}
.templates-group-header:hover { background-color: var(--c-soft); }
.templates-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--c-primary); display: inline-block; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">description</span>
            </div>
            <div>
                <h1>Templates de formulário</h1>
                @if($templatesCount > 0)
                    <p class="page-header-subtitle">{{ $templatesCount }} template(s)</p>
                @endif
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @if($templatesCount > 0)
            <div style="display: flex; gap: 2px; background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 0.5rem; padding: 2px;">
                <button type="button" onclick="filterTemplates('all')" id="filter-btn-all" class="templates-filter-btn active" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 500; border: none; cursor: pointer; background: var(--c-primary); color: #fff; transition: all 0.15s;">Todos</button>
                <button type="button" onclick="filterTemplates('ativo')" id="filter-btn-ativo" class="templates-filter-btn" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 500; border: none; cursor: pointer; background: transparent; color: var(--c-muted); transition: all 0.15s;">Ativos</button>
                <button type="button" onclick="filterTemplates('publico')" id="filter-btn-publico" class="templates-filter-btn" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 500; border: none; cursor: pointer; background: transparent; color: var(--c-muted); transition: all 0.15s;">Públicos</button>
            </div>
            @endif
            <a href="{{ route('templates.create') }}" class="btn-primary">
                <span class="material-symbols-outlined" style="font-size: 16px">add</span>
                Novo template
            </a>
        </div>
    </div>

    @if($templatesCount > 0)
    <div style="margin-bottom: 0.75rem;">
        <div style="position: relative; max-width: 20rem;">
            <span class="material-symbols-outlined" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--c-muted); pointer-events: none;">search</span>
            <input type="text" id="templates-search" placeholder="Buscar por nome..." style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.5rem; font-size: 0.875rem; border: 1px solid var(--c-border); border-radius: 0.5rem; background: var(--c-surface); color: var(--c-text); font-family: inherit;" aria-label="Buscar template por nome">
        </div>
    </div>
    @endif

    <div class="table-card" style="border-radius: 1rem; overflow: hidden;">
        <table class="templates-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--c-border);">
                    <th style="text-align: left; font-size: 0.7rem; font-weight: 700; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.06em; padding: 0.75rem 1.25rem; width: 50%;">Nome</th>
                    <th style="text-align: left; font-size: 0.7rem; font-weight: 700; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.06em; padding: 0.75rem 1rem;">Status</th>
                    <th style="text-align: right; font-size: 0.7rem; font-weight: 700; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.06em; padding: 0.75rem 1.25rem;">Ações</th>
                </tr>
            </thead>
            <tbody id="templates-table-body">
                @forelse($templatesByCategory as $categoryKey => $items)
                    @php $label = $categoryLabels[$categoryKey] ?? $categoryKey; @endphp
                    <tr class="templates-group-header templates-group-section collapsed" style="border-top: 1px solid var(--c-border);" onclick="toggleGroup(this)" data-group>
                        <td colspan="3" style="padding: 0.5rem 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="templates-chevron material-symbols-outlined" style="font-size: 14px; color: var(--c-muted)">expand_more</span>
                                <span style="font-size: 0.75rem; font-weight: 600; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.04em;">{{ $label }}</span>
                                <span style="font-size: 0.75rem; background: var(--c-soft); color: var(--c-muted); padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 500;">{{ $items->count() }}</span>
                            </div>
                        </td>
                    </tr>
                    @foreach($items as $t)
                    <tr class="templates-group-rows templates-row-hover" style="border-top: 1px solid var(--c-border); display: none;" data-name="{{ e(mb_strtolower($t->name)) }}" data-ativo="{{ $t->is_active ? '1' : '0' }}" data-publico="{{ $t->public_enabled ? '1' : '0' }}">
                        <td style="padding: 0.75rem 1.25rem; font-size: 0.8125rem;">
                            <span style="font-weight: 500; color: var(--c-text);">{{ $t->name }}</span>
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <div style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                                @if($t->is_active)
                                    <span class="templates-badge-ativo"><span class="templates-dot"></span>Ativo</span>
                                @else
                                    <span class="templates-badge-inativo">Inativo</span>
                                @endif
                                @if($t->public_enabled)
                                    <span class="templates-badge-publico"><span class="material-symbols-outlined" style="font-size: 12px">public</span> Público</span>
                                @endif
                            </div>
                        </td>
                        <td style="padding: 0.75rem 1.25rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 2px;">
                                <a href="{{ route('templates.campos.index', $t) }}" title="Campos" aria-label="Campos do template" class="templates-action-btn">
                                    <span class="material-symbols-outlined" style="font-size: 18px">tune</span>
                                </a>
                                <a href="{{ route('templates.edit', $t) }}" title="Editar" aria-label="Editar template" class="templates-action-btn">
                                    <span class="material-symbols-outlined" style="font-size: 18px">edit</span>
                                </a>
                                <form action="{{ route('templates.destroy', $t) }}" method="POST" style="display: inline;" onsubmit="return confirm('Remover este template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Remover" aria-label="Remover template" class="templates-action-btn danger">
                                        <span class="material-symbols-outlined" style="font-size: 18px">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 3rem 1rem;">
                        <span class="material-symbols-outlined" style="font-size: 36px; color: var(--c-border); display: block; margin-bottom: 8px;">description</span>
                        <span style="font-size: 0.875rem; color: var(--c-muted);">Nenhum template cadastrado.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($templatesCount > 0)
    <p style="font-size: 0.75rem; color: var(--c-muted); margin-top: 0.75rem; text-align: center;">Clique no grupo para expandir ou recolher</p>
    @endif

    <script>
    function toggleGroup(headerRow) {
        if (!headerRow.classList.contains('templates-group-header')) return;
        var next = headerRow.nextElementSibling;
        var isCollapsed = headerRow.classList.contains('collapsed');
        if (isCollapsed) {
            headerRow.classList.remove('collapsed');
            while (next && next.classList.contains('templates-group-rows')) {
                next.style.display = next.getAttribute('data-filtered') === '1' ? '' : 'none';
                next = next.nextElementSibling;
            }
        } else {
            headerRow.classList.add('collapsed');
            while (next && next.classList.contains('templates-group-rows')) {
                next.style.display = 'none';
                next = next.nextElementSibling;
            }
        }
    }
    function setActiveFilter(id) {
        document.querySelectorAll('.templates-filter-btn').forEach(function(b) {
            b.style.background = 'transparent';
            b.style.color = 'var(--c-muted)';
        });
        var btn = document.getElementById(id);
        if (btn) {
            btn.style.background = 'var(--c-primary)';
            btn.style.color = '#fff';
        }
    }
    function getCurrentFilterType() {
        var active = document.querySelector('.templates-filter-btn[style*="background: var(--c-primary)"]') || document.getElementById('filter-btn-all');
        if (!active) return 'all';
        if (active.id === 'filter-btn-ativo') return 'ativo';
        if (active.id === 'filter-btn-publico') return 'publico';
        return 'all';
    }
    function applyFilters() {
        var searchEl = document.getElementById('templates-search');
        var search = (searchEl && searchEl.value) ? searchEl.value.trim().toLowerCase() : '';
        var filterType = getCurrentFilterType();
        var rows = document.querySelectorAll('.templates-group-rows');
        var headers = document.querySelectorAll('.templates-group-header[data-group]');
        headers.forEach(function(h) { h.style.display = ''; });
        rows.forEach(function(r) {
            var matchFilter = filterType === 'all' || (filterType === 'ativo' && r.getAttribute('data-ativo') === '1') || (filterType === 'publico' && r.getAttribute('data-publico') === '1');
            var matchSearch = !search || (r.getAttribute('data-name') || '').indexOf(search) !== -1;
            var show = matchFilter && matchSearch;
            r.setAttribute('data-filtered', show ? '1' : '0');
            var prev = r.previousElementSibling;
            while (prev && !prev.classList.contains('templates-group-header')) prev = prev.previousElementSibling;
            var isCollapsed = prev && prev.classList.contains('collapsed');
            r.style.display = (show && !isCollapsed) ? '' : 'none';
        });
        hideEmptyGroups();
    }
    function filterTemplates(type) {
        setActiveFilter('filter-btn-' + type);
        applyFilters();
    }
    function hideEmptyGroups() {
        document.querySelectorAll('.templates-group-header[data-group]').forEach(function(header) {
            var next = header.nextElementSibling;
            var hasFiltered = 0;
            while (next && next.classList.contains('templates-group-rows')) {
                if (next.getAttribute('data-filtered') === '1') hasFiltered++;
                next = next.nextElementSibling;
            }
            if (hasFiltered === 0) header.style.display = 'none';
        });
    }
    (function() {
        applyFilters();
        var searchEl = document.getElementById('templates-search');
        if (searchEl) {
            searchEl.addEventListener('input', applyFilters);
            searchEl.addEventListener('keyup', applyFilters);
        }
    })();
    </script>
@endsection
