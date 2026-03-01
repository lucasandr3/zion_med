@extends('layouts.app')

@section('title', 'Configurações da clínica')

@push('styles')
<style>
/* ─── Página Configurações da Clínica (miolo) ───────────────────────────── */
.clinica-config {
    display: flex;
    flex-direction: column;
    min-height: 0;
    width: 100%;
}
.clinica-config .config-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 1px solid var(--c-border);
    margin-left: -1rem;
    margin-right: -1rem;
    margin-top: -1.5rem;
    margin-bottom: 0;
    padding: 0 1rem;
    background: var(--c-surface);
}
@media (min-width: 640px) {
    .clinica-config .config-tabs { margin-left: -1.5rem; margin-right: -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; }
}
@media (min-width: 1024px) {
    .clinica-config .config-tabs { margin-left: -2rem; margin-right: -2rem; margin-top: -2rem; padding-left: 2rem; padding-right: 2rem; }
}
.clinica-config .config-tab {
    padding: 14px 18px;
    font-size: 13px;
    font-weight: 500;
    color: var(--c-muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.15s;
    white-space: nowrap;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    font-family: inherit;
}
.clinica-config .config-tab:hover { color: var(--c-text); }
.clinica-config .config-tab.active { color: var(--c-primary); border-bottom-color: var(--c-primary); }

.clinica-config .config-panel { display: none; }
.clinica-config .config-panel.active { display: block; }

.clinica-config .config-progress {
    margin-bottom: 20px;
    margin-top: 24px;
}
.clinica-config .config-progress-bar {
    background: var(--c-soft);
    border-radius: 999px;
    height: 4px;
    margin-top: 6px;
}
.clinica-config .config-progress-bar .fill {
    height: 4px;
    border-radius: 999px;
    background: var(--c-primary);
    transition: width 0.3s;
}

.clinica-config .section-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}
.clinica-config .section-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--c-border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.clinica-config .section-header .icon-wrap {
    width: 28px;
    height: 28px;
    background: color-mix(in srgb, var(--c-primary) 12%, transparent);
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.clinica-config .section-header .icon-wrap .material-symbols-outlined {
    font-size: 14px;
    color: var(--c-primary);
}
.clinica-config .section-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.clinica-config .section-body { padding: 20px; }

.clinica-config .field { margin-bottom: 18px; }
.clinica-config .field:last-child { margin-bottom: 0; }
.clinica-config .field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--c-text);
    margin-bottom: 6px;
}
.clinica-config .field label .req { color: var(--c-primary); margin-left: 3px; }
.clinica-config .field label .opt { font-size: 10px; font-weight: 400; color: var(--c-muted); margin-left: 6px; }
.clinica-config .field .hint { font-size: 11px; color: var(--c-muted); margin-top: 5px; }
.clinica-config .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 540px) {
    .clinica-config .field-row { grid-template-columns: 1fr; }
}

.clinica-config .schedule-grid { display: flex; flex-direction: column; gap: 6px; }
.clinica-config .schedule-row {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--c-soft);
    border: 1px solid var(--c-border);
    border-radius: 8px;
    padding: 8px 14px;
    transition: border 0.15s;
}
.clinica-config .schedule-row.active { border-color: color-mix(in srgb, var(--c-primary) 30%, transparent); }
.clinica-config .day-label { width: 72px; font-size: 12px; font-weight: 500; color: var(--c-text); }
.clinica-config .schedule-row .toggle-wrap {
    position: relative;
    width: 34px;
    height: 18px;
    flex-shrink: 0;
}
.clinica-config .schedule-row .toggle-wrap input { opacity: 0; width: 0; height: 0; }
.clinica-config .schedule-row .toggle-track {
    position: absolute;
    inset: 0;
    background: var(--c-border);
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.2s;
}
.clinica-config .schedule-row .toggle-wrap input:checked + .toggle-track { background: var(--c-primary); }
.clinica-config .schedule-row .toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
    pointer-events: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.clinica-config .schedule-row .toggle-wrap input:checked ~ .toggle-thumb { transform: translateX(16px); }
.clinica-config .time-inputs { display: flex; align-items: center; gap: 6px; flex: 1; flex-wrap: wrap; }
.clinica-config .time-input {
    background: var(--c-bg);
    border: 1px solid var(--c-border);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    color: var(--c-text);
    font-family: inherit;
    outline: none;
    width: 70px;
}
.clinica-config .time-sep { font-size: 11px; color: var(--c-muted); }
.clinica-config .copy-schedule-btn {
    font-size: 10.5px;
    color: var(--c-primary);
    cursor: pointer;
    margin-left: auto;
    background: none;
    border: none;
    padding: 0;
    font-family: inherit;
}
.clinica-config .copy-schedule-btn:hover { text-decoration: underline; }
.clinica-config .closed-tag {
    font-size: 10.5px;
    color: var(--c-muted);
    background: var(--c-bg);
    border-radius: 4px;
    padding: 2px 7px;
}

.clinica-config .upload-zone {
    border: 2px dashed var(--c-border);
    border-radius: 10px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: border 0.15s, background 0.15s;
}
.clinica-config .upload-zone:hover {
    border-color: var(--c-primary);
    background: color-mix(in srgb, var(--c-primary) 4%, transparent);
}
.clinica-config .upload-zone .material-symbols-outlined { font-size: 28px; color: var(--c-muted); }
.clinica-config .upload-zone .upload-label { font-size: 13px; font-weight: 500; color: var(--c-text); }
.clinica-config .upload-zone .upload-hint { font-size: 11px; color: var(--c-muted); }

.clinica-config .theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 8px;
}
.clinica-config .theme-card {
    border: 2px solid var(--c-border);
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
    overflow: hidden;
    background: var(--c-surface);
}
.clinica-config .theme-card:hover { border-color: var(--c-muted); }
.clinica-config .theme-card.selected { border-color: var(--c-primary); }
.clinica-config .theme-card.selected::after {
    content: '✓';
    position: absolute;
    top: 4px;
    right: 6px;
    font-size: 10px;
    color: var(--c-primary);
    font-weight: 700;
}
.clinica-config .theme-preview {
    height: 32px;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    gap: 2px;
    align-items: flex-end;
    padding: 4px;
}
.clinica-config .theme-preview .bar {
    border-radius: 2px;
    width: 5px;
    flex-shrink: 0;
}
.clinica-config .theme-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    margin-bottom: 6px;
}
.clinica-config .theme-name { font-size: 10.5px; font-weight: 500; color: var(--c-text); line-height: 1.3; }

.clinica-config .dark-toggle-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--c-soft);
    border: 1px solid var(--c-border);
    border-radius: 10px;
    padding: 14px 16px;
}
.clinica-config .dark-toggle-info .dtitle { font-size: 13px; font-weight: 600; color: var(--c-text); }
.clinica-config .dark-toggle-info .dsub { font-size: 11.5px; color: var(--c-muted); margin-top: 2px; }
.clinica-config .dark-toggle-card .toggle-wrap {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.clinica-config .dark-toggle-card .toggle-wrap input { opacity: 0; width: 0; height: 0; }
.clinica-config .dark-toggle-card .toggle-track {
    position: absolute;
    inset: 0;
    background: var(--c-border);
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.2s;
}
.clinica-config .dark-toggle-card .toggle-wrap input:checked + .toggle-track { background: var(--c-primary); }
.clinica-config .dark-toggle-card .toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
    pointer-events: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.clinica-config .dark-toggle-card .toggle-wrap input:checked ~ .toggle-thumb { transform: translateX(20px); }

/* Altura igual ao bloco de usuário da sidebar (#sidebar-footer: py-2 + py-2 + avatar 28px + 2 linhas texto) ≈ 4rem */
.clinica-config .sticky-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    min-height: 4rem;
    height: 4rem;
    background: var(--c-surface);
    border-top: 1px solid var(--c-border);
    padding: 0 1rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    z-index: 50;
}
@media (min-width: 640px) {
    .clinica-config .sticky-footer { padding-left: 1.5rem; padding-right: 1.5rem; }
}
@media (min-width: 1024px) {
    .clinica-config .sticky-footer {
        left: var(--sidebar-w);
        padding-left: 2rem;
        padding-right: 2rem;
    }
}
.clinica-config .sticky-footer-spacer {
    height: 4rem;
    flex-shrink: 0;
}
.clinica-config .footer-btns { display: flex; gap: 10px; }
.clinica-config .btn-secondary {
    padding: 9px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--c-border);
    background: transparent;
    color: var(--c-muted);
    font-family: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
}
.clinica-config .btn-secondary:hover { color: var(--c-text); }
</style>
@endpush

@section('content')
<div class="clinica-config">
    <form action="{{ route('clinica.configuracoes.update') }}" method="POST" enctype="multipart/form-data" id="clinica-config-form">
        @csrf
        @method('PUT')

        {{-- Tabs --}}
        <div class="config-tabs" role="tablist">
            <button type="button" class="config-tab active" role="tab" aria-selected="true" data-tab="dados">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">description</span>
                Dados Gerais
            </button>
            <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="publica">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">public</span>
                Página Pública
            </button>
            <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="visual">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">palette</span>
                Tema Visual
            </button>
        </div>

        <div class="config-progress">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:12px;font-weight:600;color:var(--c-muted)">Perfil da clínica</span>
                <span style="font-size:11px;color:var(--c-primary)" id="progress-text">—</span>
            </div>
            <div class="config-progress-bar"><div class="fill" id="progress-fill" style="width:0%"></div></div>
        </div>

        {{-- Painel: Dados Gerais --}}
        <div id="panel-dados" class="config-panel active" role="tabpanel">
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">business</span>
                    </div>
                    <span class="section-title">Informações Básicas</span>
                </div>
                <div class="section-body">
                    <div class="field">
                        <label>Nome da clínica <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $clinic->name) }}" required class="form-input" placeholder="Nome da sua clínica">
                        @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>E-mail para notificações <span class="req">*</span></label>
                            <input type="email" name="notification_email" value="{{ old('notification_email', $clinic->notification_email) }}" class="form-input" placeholder="recepcao@clinica.com">
                            @error('notification_email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label>Telefone / WhatsApp</label>
                            <input type="text" name="phone" value="{{ old('phone', $clinic->phone) }}" class="form-input" placeholder="(11) 99999-9999">
                            @error('phone')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="field">
                        <label>Endereço</label>
                        <input type="text" name="address" value="{{ old('address', $clinic->address) }}" class="form-input" placeholder="Endereço completo">
                        <div class="hint">Aparece no cabeçalho dos PDFs e na página do Link Bio.</div>
                        @error('address')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>E-mail de contato público <span class="opt">opcional</span></label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $clinic->contact_email) }}" class="form-input" placeholder="contato@clinica.com">
                        <div class="hint">Exibido na página do Link Bio. Diferente do e-mail de notificações.</div>
                        @error('contact_email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Horário --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <span class="section-title">Horário de Atendimento</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--c-muted)">Deixe vazio para "Fechado"</span>
                </div>
                <div class="section-body">
                    <div class="schedule-grid" id="scheduleGrid">
                        @php
                            $days = ['1'=>'Segunda','2'=>'Terça','3'=>'Quarta','4'=>'Quinta','5'=>'Sexta','6'=>'Sábado','7'=>'Domingo'];
                            $bh = old('business_hours', $clinic->business_hours) ?? [];
                        @endphp
                        @foreach($days as $d => $label)
                            @php
                                $slot = is_array($bh[$d] ?? null) ? $bh[$d] : [];
                                $open = $slot['open'] ?? '';
                                $close = $slot['close'] ?? '';
                                $active = $open !== '' || $close !== '';
                                if (!$active) { $open = '08:00'; $close = '18:00'; }
                            @endphp
                            <div class="schedule-row {{ $active ? 'active' : '' }}" data-day="{{ $d }}">
                                <span class="day-label">{{ $label }}</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" class="schedule-open-toggle" {{ $active ? 'checked' : '' }} data-day="{{ $d }}">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                                <div class="time-inputs" style="{{ $active ? '' : 'display:none' }}">
                                    <span class="time-sep" style="font-size:10px;color:var(--c-muted)">Abre</span>
                                    <input class="form-input flatpickr-time time-input" type="text" name="business_hours[{{ $d }}][open]" value="{{ $open }}" placeholder="08:00" data-day="{{ $d }}" autocomplete="off" style="padding: 16px 8px;">
                                    <span class="time-sep">→</span>
                                    <span class="time-sep" style="font-size:10px;color:var(--c-muted)">Fecha</span>
                                    <input class="form-input flatpickr-time time-input" type="text" name="business_hours[{{ $d }}][close]" value="{{ $close }}" placeholder="18:00" data-day="{{ $d }}" autocomplete="off" style="padding: 16px 8px;">
                                </div>
                                <span class="closed-tag" style="{{ $active ? 'display:none' : '' }}">Fechado</span>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:10px;display:flex;justify-content:flex-end;">
                        <button type="button" class="copy-schedule-btn" id="copyFirstToAll">Copiar segunda para todos os dias →</button>
                    </div>
                    @error('business_hours')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Logo --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">image</span>
                    </div>
                    <span class="section-title">Logo da Clínica</span>
                </div>
                <div class="section-body">
                    @if($clinic->logo_path)
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                            <img src="{{ asset('storage/'.$clinic->logo_path) }}" alt="Logo" style="height:40px;border-radius:8px;border:1px solid var(--c-border);object-fit:contain;padding:4px;background:var(--c-soft)">
                            <span style="font-size:12px;color:var(--c-muted)">Logo atual</span>
                        </div>
                    @endif
                    <div class="upload-zone" id="logo-upload-zone" onclick="document.getElementById('logo-input').click()">
                        <span class="material-symbols-outlined">upload_file</span>
                        <div class="upload-label">Arraste sua logo aqui ou <span style="color:var(--c-primary)">clique para escolher</span></div>
                        <div class="upload-hint">PNG, JPG ou SVG • Recomendado: 512×512px • Máx 2MB</div>
                    </div>
                    <input type="file" name="logo" id="logo-input" accept="image/*" class="sr-only" aria-hidden="true">
                    @error('logo')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Painel: Página Pública --}}
        <div id="panel-publica" class="config-panel" role="tabpanel" hidden>
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">public</span>
                    </div>
                    <span class="section-title">Página Pública (Link Bio)</span>
                </div>
                <div class="section-body">
                    <div class="field">
                        <label>Descrição / Slogan <span class="opt">opcional</span></label>
                        <input type="text" name="short_description" value="{{ old('short_description', $clinic->short_description) }}" class="form-input" placeholder="Ex: Cuidando da sua saúde com excelência" maxlength="200">
                        @error('short_description')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Especialidades <span class="opt">opcional</span></label>
                        <input type="text" name="specialties" value="{{ old('specialties', $clinic->specialties) }}" class="form-input" placeholder="Clínica geral, Pediatria, Dermatologia (separadas por vírgula)">
                        @error('specialties')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Ano de fundação <span class="opt">opcional</span></label>
                            <input type="number" name="founded_year" value="{{ old('founded_year', $clinic->founded_year) }}" class="form-input" placeholder="2010" min="1900" max="{{ date('Y') }}">
                            @error('founded_year')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label>Link do Google Maps <span class="opt">opcional</span></label>
                            <input type="url" name="maps_url" value="{{ old('maps_url', $clinic->maps_url) }}" class="form-input" placeholder="https://maps.google.com/...">
                            @error('maps_url')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="field">
                        <label>Meta description (SEO) <span class="opt">opcional</span></label>
                        <textarea name="meta_description" rows="3" class="form-input" placeholder="Breve descrição para buscadores e redes sociais" maxlength="300" style="resize:vertical">{{ old('meta_description', $clinic->meta_description) }}</textarea>
                        <div class="hint">Recomendado: até 160 caracteres.</div>
                        @error('meta_description')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Imagem de capa</label>
                        @if($clinic->cover_image_path)
                            <div style="margin-bottom:8px">
                                <img src="{{ asset('storage/'.$clinic->cover_image_path) }}" alt="Capa" style="max-height:120px;border-radius:8px;border:1px solid var(--c-border);object-fit:cover">
                            </div>
                        @endif
                        <div class="upload-zone" onclick="document.getElementById('cover-input').click()">
                            <span class="material-symbols-outlined">upload_file</span>
                            <div class="upload-label">Arraste a capa ou <span style="color:var(--c-primary)">clique para escolher</span></div>
                            <div class="upload-hint">Banner do topo do Link Bio • Recomendado: 1200×400px</div>
                        </div>
                        <input type="file" name="cover_image" id="cover-input" accept="image/*" class="sr-only" aria-hidden="true">
                        @error('cover_image')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Painel: Tema Visual --}}
        <div id="panel-visual" class="config-panel" role="tabpanel" hidden>
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">palette</span>
                    </div>
                    <span class="section-title">Tema de Cores</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--c-muted)">Aplicado para todos os usuários</span>
                </div>
                <div class="section-body">
                    <div class="theme-grid" id="themeGrid">
                        @php $currentTheme = old('theme', $clinic->theme ?? 'ocean-blue'); @endphp
                        @foreach($availableThemes as $key => $meta)
                            @php $p = $meta['primary']; $bg = $p . '22'; @endphp
                            <label class="theme-card {{ $currentTheme === $key ? 'selected' : '' }}">
                                <input type="radio" name="theme" value="{{ $key }}" {{ $currentTheme === $key ? 'checked' : '' }} class="sr-only">
                                <div class="theme-preview" style="background:{{ $bg }};">
                                    <div class="bar" style="background:{{ $p }}; height:12px;"></div>
                                    <div class="bar" style="background:{{ $p }}; height:18px;"></div>
                                    <div class="bar" style="background:{{ $p }}; height:24px;"></div>
                                </div>
                                <div class="theme-dot" style="background:{{ $p }}"></div>
                                <div class="theme-name">{{ $meta['label'] }}</div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">dark_mode</span>
                    </div>
                    <span class="section-title">Modo Escuro</span>
                </div>
                <div class="section-body">
                    <div class="dark-toggle-card">
                        <div class="dark-toggle-info">
                            <div class="dtitle">Ativar modo escuro</div>
                            <div class="dsub">Fundo escuro profundo, independente do tema de cor selecionado.</div>
                        </div>
                        <label class="toggle-wrap">
                            <input type="hidden" name="dark_mode" value="0">
                            <input type="checkbox" name="dark_mode" id="dark_mode" value="1" {{ old('dark_mode', $clinic->dark_mode) ? 'checked' : '' }} class="sr-only">
                            <span class="toggle-track"></span>
                            <span class="toggle-thumb"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Espaço para o footer fixo não cobrir o conteúdo --}}
        <div class="sticky-footer-spacer" aria-hidden="true"></div>
        {{-- Sticky Footer (fixo no bottom, mesma altura do bloco usuário da sidebar) --}}
        <div class="sticky-footer">
            <div style="font-size:12px;color:var(--c-muted)">Alterações não salvas</div>
            <div class="footer-btns">
                <a href="{{ route('dashboard') }}" class="btn-secondary">
                    <span class="material-symbols-outlined" style="font-size:16px">close</span>
                    Cancelar
                </a>
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">save</span>
                    Salvar configurações
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    var form = document.getElementById('clinica-config-form');
    if (!form) return;

    // Tabs
    form.querySelectorAll('.config-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            form.querySelectorAll('.config-tab').forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            form.querySelectorAll('.config-panel').forEach(function(p) {
                p.classList.remove('active');
                p.hidden = true;
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            var panel = document.getElementById('panel-' + tabId);
            if (panel) {
                panel.classList.add('active');
                panel.hidden = false;
            }
        });
    });

    // Schedule toggles
    form.querySelectorAll('.schedule-open-toggle').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var row = this.closest('.schedule-row');
            var day = this.getAttribute('data-day');
            var timeInputs = row.querySelector('.time-inputs');
            var closedTag = row.querySelector('.closed-tag');
            var openInp = row.querySelector('input[name="business_hours[' + day + '][open]"]');
            var closeInp = row.querySelector('input[name="business_hours[' + day + '][close]"]');
            if (this.checked) {
                row.classList.add('active');
                if (timeInputs) timeInputs.style.display = 'flex';
                if (closedTag) closedTag.style.display = 'none';
                if (openInp && !openInp.value) openInp.value = '08:00';
                if (closeInp && !closeInp.value) closeInp.value = '18:00';
            } else {
                row.classList.remove('active');
                if (timeInputs) timeInputs.style.display = 'none';
                if (closedTag) closedTag.style.display = 'inline';
                if (openInp) openInp.value = '';
                if (closeInp) closeInp.value = '';
            }
        });
    });

    document.getElementById('copyFirstToAll').addEventListener('click', function() {
        var firstRow = form.querySelector('.schedule-row[data-day="1"]');
        if (!firstRow) return;
        var openInp = firstRow.querySelector('input[name="business_hours[1][open]"]');
        var closeInp = firstRow.querySelector('input[name="business_hours[1][close]"]');
        var openVal = openInp ? openInp.value : '08:00';
        var closeVal = closeInp ? closeInp.value : '18:00';
        [2,3,4,5].forEach(function(day) {
            var row = form.querySelector('.schedule-row[data-day="' + day + '"]');
            if (!row) return;
            var toggle = row.querySelector('.schedule-open-toggle');
            if (toggle) toggle.checked = true;
            toggle.dispatchEvent(new Event('change'));
            var o = row.querySelector('input[name="business_hours[' + day + '][open]"]');
            var c = row.querySelector('input[name="business_hours[' + day + '][close]"]');
            if (o) o.value = openVal;
            if (c) c.value = closeVal;
        });
    });

    // Theme cards: visual selected state
    form.querySelectorAll('.theme-card').forEach(function(card) {
        card.addEventListener('click', function() {
            form.querySelectorAll('.theme-card').forEach(function(c) { c.classList.remove('selected'); });
            this.classList.add('selected');
        });
    });

    // Dark mode toggle visual
    var darkCb = form.querySelector('#dark_mode');
    var darkTrack = form.querySelector('.dark-toggle-card .toggle-track');
    var darkThumb = form.querySelector('.dark-toggle-card .toggle-thumb');
    function syncDarkToggle(checked) {
        if (darkTrack) darkTrack.style.background = checked ? 'var(--c-primary)' : 'var(--c-border)';
        if (darkThumb) darkThumb.style.transform = checked ? 'translateX(20px)' : 'translateX(0)';
    }
    if (darkCb) {
        syncDarkToggle(darkCb.checked);
        darkCb.addEventListener('change', function() { syncDarkToggle(this.checked); });
    }

    // Progress (filled fields count)
    var progressFields = form.querySelectorAll('input[name="name"], input[name="notification_email"], input[name="address"], input[name="phone"], input[name="contact_email"], input[name="short_description"], input[name="specialties"], textarea[name="meta_description"]');
    var progressFill = document.getElementById('progress-fill');
    var progressText = document.getElementById('progress-text');
    function updateProgress() {
        var n = 0;
        var total = 8;
        progressFields.forEach(function(el) {
            if (el && (el.value || '').trim().length > 0) n++;
        });
        var pct = total ? Math.round((n / total) * 100) : 0;
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressText) progressText.textContent = n + ' de ' + total + ' campos preenchidos';
    }
    progressFields.forEach(function(el) {
        if (el) el.addEventListener('input', updateProgress);
    });
    updateProgress();
})();
</script>
@endsection
