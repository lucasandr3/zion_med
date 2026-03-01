@extends('layouts.app')

@section('title', 'Campos - ' . $template->name)
@section('header_back_url', route('templates.index'))
@section('header_back_label', 'Voltar para Templates')

@section('content')
    {{-- Header (padrão page-header + page-title) --}}
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">tune</span>
            </div>
            <div>
                <h1>{{ $template->name }}<span style="color: var(--c-muted); font-weight: 400; margin: 0 0.25rem;">—</span> Campos</h1>
                <p class="page-header-subtitle">Gerencie os campos do seu formulário</p>
            </div>
        </div>
        <div style="display: flex; gap: 0.625rem; flex-wrap: wrap; align-items: center;">
            <a href="{{ route('templates.edit', $template) }}" class="btn btn-ghost">
                <span class="material-symbols-outlined" style="font-size:14px">edit</span>
                Editar template
            </a>
            @if($template->public_token)
                @php $publicFullUrl = url()->route('formulario-publico.show', $template->public_token); @endphp
                <button type="button" data-copy-url="{{ $publicFullUrl }}" onclick="copyPublicLinkFromBtn(this)"
                        class="btn btn-primary">
                    <span class="material-symbols-outlined" style="font-size:14px">content_copy</span>
                    <span class="copy-label">Copiar link</span>
                </button>
                <a href="{{ $publicFullUrl }}" target="_blank" class="btn btn-ghost">
                    <span class="material-symbols-outlined" style="font-size:14px">open_in_new</span>
                    Abrir
                </a>
                <form action="{{ route('templates.link.desativar', $template) }}" method="POST" class="inline" onsubmit="return confirm('Desativar link público?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost">
                        <span class="material-symbols-outlined" style="font-size:14px">link_off</span>
                        Desativar link
                    </button>
                </form>
            @else
                <form action="{{ route('templates.link.gerar', $template) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size:14px">link</span>
                        Gerar link público
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Public URL notice --}}
    @if(session('public_url'))
    <div class="mb-6 p-4 rounded-xl border" style="background: color-mix(in srgb, var(--c-primary) 8%, var(--c-surface)); border-color: color-mix(in srgb, var(--c-primary) 25%, var(--c-border));">
        <div class="flex items-center gap-2 mb-1">
            <span class="material-symbols-outlined" style="font-size:18px; color: var(--c-primary)">check_circle</span>
            <p class="text-sm font-semibold" style="color: var(--c-text)">Link público gerado:</p>
        </div>
        <div class="flex items-start gap-2 mt-1">
            <code id="new-public-url" class="flex-1 break-all text-sm rounded-lg px-3 py-2" style="color: var(--c-primary); background: color-mix(in srgb, var(--c-primary) 12%, transparent);">{{ session('public_url') }}</code>
            <button type="button" onclick="copyPublicLink(document.getElementById('new-public-url').textContent, this)"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg font-medium text-sm text-white transition-colors"
                    style="background: var(--c-primary);">
                <span class="material-symbols-outlined" style="font-size:16px">content_copy</span>
                <span class="copy-label">Copiar</span>
            </button>
        </div>
    </div>
    @endif
    <script>
    function copyPublicLink(url, btn) {
        navigator.clipboard.writeText(url).then(function() {
            var lbl = btn && btn.querySelector && btn.querySelector('.copy-label');
            if (lbl) { var t = lbl.textContent; lbl.textContent = 'Copiado!'; setTimeout(function(){ lbl.textContent = t; }, 2000); }
        });
    }
    function copyPublicLinkFromBtn(btn) { copyPublicLink(btn.getAttribute('data-copy-url') || '', btn); }
    </script>

    {{-- Card Adicionar campo --}}
    <div class="campos-add-card">
        <div class="campos-card-header">
            <span class="material-symbols-outlined">add_circle</span>
            <span class="campos-card-header-title">Adicionar campo</span>
            <span class="campos-badge">Novo</span>
        </div>
        <form action="{{ route('templates.campos.store', $template) }}" method="POST">
            @csrf
            <div class="campos-form-body">
                <div class="campos-form-grid">
                    <div class="campos-form-field">
                        <label class="campos-form-label" for="new_type">Tipo <span class="required">*</span></label>
                        <select name="type" id="new_type" required class="form-select">
                            <option value="text">Texto curto</option>
                            <option value="textarea">Texto longo</option>
                            <option value="number">Número</option>
                            <option value="date">Data</option>
                            <option value="select">Select</option>
                            <option value="radio">Radio</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="file">Anexo</option>
                            <option value="signature">Assinatura</option>
                        </select>
                    </div>
                    <div class="campos-form-field">
                        <label class="campos-form-label" for="new_label">Rótulo <span class="required">*</span></label>
                        <input type="text" name="label" id="new_label" required class="form-input" placeholder="Ex: Nome completo">
                    </div>
                    <div class="campos-form-field">
                        <label class="campos-form-label" for="new_name_key">Chave (name_key) <span class="required">*</span></label>
                        <input type="text" name="name_key" id="new_name_key" required pattern="[a-z0-9_]+" maxlength="80" class="form-input" placeholder="ex: nome_completo" title="Apenas letras minúsculas, números e underscore" style="font-family: ui-monospace, monospace; font-size: 0.8125rem;">
                    </div>
                </div>
                <div id="options_block" class="campos-form-field" style="margin-bottom: 1rem; display: none;">
                    <label class="campos-form-label" for="new_options_text">Lista de opções que o usuário poderá escolher</label>
                    <p class="text-xs mb-1.5" style="color: var(--c-muted);">Para Select e Radio. Digite uma opção por linha.</p>
                    <textarea name="options_text" id="new_options_text" rows="3" class="form-input" placeholder="Ex.: Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
                </div>
                <div class="campos-form-footer">
                    <div class="campos-toggle-group">
                        <input type="hidden" name="required" value="0">
                        <input type="checkbox" name="required" id="new_required" value="1" class="form-checkbox">
                        <label for="new_required" class="campos-toggle-label">Campo obrigatório</label>
                    </div>
                    <button type="submit" class="campos-btn-add">
                        <span class="material-symbols-outlined" style="font-size:14px">add</span>
                        Adicionar campo
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabela de campos --}}
    <div class="campos-table-card">
        <div class="campos-table-topbar">
            <div class="campos-table-topbar-title">
                Campos configurados
                <span class="campos-count-badge">{{ $template->fields->count() }}</span>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:50px"></th>
                    <th style="width:60px">Ordem</th>
                    <th>Tipo</th>
                    <th>Rótulo</th>
                    <th>Chave</th>
                    <th style="width:100px; text-align:center">Req.</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($template->fields as $c)
                <tr>
                    <td><span class="campos-drag-handle" aria-hidden="true">⠿</span></td>
                    <td style="color: var(--c-muted); font-weight: 600; font-size: 0.8125rem;">{{ str_pad((string)$c->sort_order, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        @php
                            $typeIcons = ['text' => 'text_fields', 'textarea' => 'notes', 'number' => 'numbers', 'date' => 'calendar_today', 'select' => 'arrow_drop_down_circle', 'radio' => 'radio_button_checked', 'checkbox' => 'check_box', 'file' => 'attach_file', 'signature' => 'draw'];
                            $icon = $typeIcons[$c->type] ?? 'tune';
                        @endphp
                        <span class="campos-type-pill">
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                            {{ $c->type }}
                        </span>
                    </td>
                    <td style="font-weight: 500;">{{ $c->label }}</td>
                    <td><span class="campos-key-chip">{{ $c->name_key }}</span></td>
                    <td style="text-align:center">
                        @if($c->required)
                            <span class="campos-required-dot" title="Obrigatório"></span>
                        @else
                            <span style="color: var(--c-border); font-size: 0.75rem;">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="campos-actions">
                            <a href="#" class="campos-icon-btn editar-campo" data-tooltip="Editar" aria-label="Editar campo"
                               data-id="{{ $c->id }}" data-type="{{ $c->type }}" data-label="{{ $c->label }}"
                               data-name_key="{{ $c->name_key }}" data-required="{{ $c->required ? '1' : '0' }}"
                               data-options="{{ e(json_encode($c->getOptionsList())) }}"
                               data-update-url="{{ route('templates.campos.update', [$template, $c]) }}">
                                <span class="material-symbols-outlined" style="font-size:13px">edit</span>
                            </a>
                            <form action="{{ route('templates.campos.destroy', [$template, $c]) }}" method="POST" class="inline" onsubmit="return confirm('Remover este campo?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="campos-icon-btn danger" aria-label="Remover campo">
                                    <span class="material-symbols-outlined" style="font-size:13px">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-0!">
                        <div class="campos-empty">
                            <div class="campos-empty-icon">
                                <span class="material-symbols-outlined">view_list</span>
                            </div>
                            <p class="campos-empty-title">Nenhum campo adicionado</p>
                            <p class="campos-empty-sub">Use o formulário acima para adicionar campos ao template.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

{{-- Modal Editar campo --}}
<div id="modal-editar-campo" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.4); display: none;">
    <div class="rounded-xl border w-full max-w-lg overflow-hidden" style="background: var(--c-surface); border-color: var(--c-border); box-shadow: 0 10px 40px rgba(0,0,0,0.2); color: var(--c-text);">
        <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--c-border);">
            <h2 class="font-semibold text-lg">Editar campo</h2>
            <button type="button" id="modal-editar-fechar" class="p-2 rounded-lg transition-colors hover:bg-bg-soft" style="color: var(--c-muted);" aria-label="Fechar">
                <span class="material-symbols-outlined" style="font-size:22px">close</span>
            </button>
        </div>
        <form id="form-editar-campo" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="p-4 space-y-4">
                <div>
                    <label class="campos-form-label" for="edit_type">Tipo</label>
                    <select name="type" id="edit_type" required class="form-select w-full">
                        <option value="text">Texto curto</option>
                        <option value="textarea">Texto longo</option>
                        <option value="number">Número</option>
                        <option value="date">Data</option>
                        <option value="select">Select</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="file">Anexo</option>
                        <option value="signature">Assinatura</option>
                    </select>
                </div>
                <div>
                    <label class="campos-form-label" for="edit_label">Rótulo</label>
                    <input type="text" name="label" id="edit_label" required class="form-input w-full" placeholder="Ex: Nome completo">
                </div>
                <div>
                    <label class="campos-form-label" for="edit_name_key">Chave (name_key)</label>
                    <input type="text" name="name_key" id="edit_name_key" required pattern="[a-z0-9_]+" maxlength="80" class="form-input w-full" placeholder="ex: nome_completo" title="Apenas letras minúsculas, números e underscore" style="font-family: ui-monospace, monospace;">
                    <p class="text-xs mt-1" style="color: var(--c-muted);">Apenas letras minúsculas, números e underscore.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="required" value="0">
                    <input type="checkbox" name="required" id="edit_required" value="1" class="form-checkbox">
                    <label for="edit_required" class="text-sm" style="color: var(--c-text);">Obrigatório</label>
                </div>
                <div id="edit_options_block" style="display: none;">
                    <label class="campos-form-label" for="edit_options_text">Lista de opções que o usuário poderá escolher</label>
                    <p class="text-xs mb-1.5" style="color: var(--c-muted);">Para Select e Radio. Digite uma opção por linha.</p>
                    <textarea name="options_text" id="edit_options_text" rows="3" class="form-input w-full" placeholder="Ex.: Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 p-4 border-t" style="border-color: var(--c-border);">
                <button type="button" id="modal-editar-cancelar" class="px-4 py-2 rounded-lg font-medium transition-colors" style="background: var(--c-soft); color: var(--c-text);">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-lg font-medium text-white transition-colors" style="background: var(--c-primary);">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var newType = document.getElementById('new_type');
    var optionsBlock = document.getElementById('options_block');

    function updateOptionsBlockVisibility() {
        var show = newType && ['select', 'radio'].includes(newType.value);
        if (optionsBlock) {
            optionsBlock.style.display = show ? '' : 'none';
        }
    }

    if (newType) {
        newType.addEventListener('change', updateOptionsBlockVisibility);
        updateOptionsBlockVisibility();
    }

    var modal = document.getElementById('modal-editar-campo');
    var form = document.getElementById('form-editar-campo');
    var editType = document.getElementById('edit_type');
    var editOptionsBlock = document.getElementById('edit_options_block');

    function openModal() { modal.classList.remove('hidden'); modal.style.display = 'flex'; }
    function closeModal() { modal.classList.add('hidden'); modal.style.display = 'none'; }

    function toggleEditOptions() {
        var show = editType && ['select', 'radio'].includes(editType.value);
        if (editOptionsBlock) editOptionsBlock.style.display = show ? '' : 'none';
    }

    if (editType) editType.addEventListener('change', toggleEditOptions);

    document.querySelectorAll('.editar-campo').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var type = this.getAttribute('data-type') || 'text';
            var label = this.getAttribute('data-label') || '';
            var nameKey = this.getAttribute('data-name_key') || '';
            var required = this.getAttribute('data-required') === '1';
            var options = [];
            try { options = JSON.parse(this.getAttribute('data-options') || '[]'); } catch (_) {}
            var updateUrl = this.getAttribute('data-update-url') || '';

            form.action = updateUrl;
            editType.value = type;
            document.getElementById('edit_label').value = label;
            document.getElementById('edit_name_key').value = nameKey;
            document.getElementById('edit_required').checked = required;
            document.getElementById('edit_options_text').value = Array.isArray(options) ? options.join('\n') : '';
            toggleEditOptions();
            openModal();
        });
    });

    document.getElementById('modal-editar-fechar').addEventListener('click', closeModal);
    document.getElementById('modal-editar-cancelar').addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
})();
</script>
@endsection
