@extends('layouts.app')

@section('title', 'Configurações da empresa')

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
    .clinica-config .config-tabs { margin-left: -1.5rem; margin-right: -1.5rem; margin-top: -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; }
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

/* Aba Assinatura */
.clinica-config .status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
}
.clinica-config .status-badge.trial { background: rgba(59,130,246,0.12); color: #2563eb; }
.clinica-config .status-badge.active { background: rgba(34,197,94,0.12); color: #16a34a; }
.clinica-config .status-badge.past_due { background: rgba(234,179,8,0.12); color: #ca8a04; }
.clinica-config .status-badge.blocked { background: rgba(239,68,68,0.12); color: #dc2626; }
.clinica-config .status-badge.inactive { background: var(--c-soft); color: var(--c-muted); }
.clinica-config .billing-meta-line { font-size: 13px; color: var(--c-muted); margin-top: 8px; }
.clinica-config .billing-meta-line strong { color: var(--c-text); }
.clinica-config .btn-regularize {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
    background: var(--c-primary); color: #fff; border: none; cursor: pointer;
    text-decoration: none; font-family: inherit;
}
.clinica-config .btn-regularize:hover { opacity: 0.92; }
.clinica-config .plan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.clinica-config .plan-card {
    border: 1px solid var(--c-border); border-radius: 12px; padding: 20px;
    background: var(--c-surface); transition: border-color 0.15s;
}
.clinica-config .plan-card:hover { border-color: var(--c-primary); }
.clinica-config .plan-card .plan-name { font-size: 1rem; font-weight: 700; color: var(--c-text); margin-bottom: 4px; }
.clinica-config .plan-card .plan-desc { font-size: 12px; color: var(--c-muted); margin-bottom: 12px; }
.clinica-config .plan-card .plan-value { font-size: 1.25rem; font-weight: 700; color: var(--c-primary); margin-bottom: 14px; }
.clinica-config .plan-card .plan-value small { font-size: 0.75rem; font-weight: 500; color: var(--c-muted); }
.clinica-config #panel-assinatura { padding-top: 1.5rem; }
.clinica-config #panel-assinatura .section-card { margin-bottom: 24px; }
.clinica-config #panel-assinatura .section-card:last-child { margin-bottom: 0; }

.clinica-config .config-page-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
}
.clinica-config .config-page-alert-error {
    border: 1px solid rgba(239,68,68,0.25);
    background: rgba(239,68,68,0.08);
    color: #ef4444;
}
#planos-assinatura.highlight-scroll {
    animation: planos-highlight 0.6s ease-out;
}
@keyframes planos-highlight {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.45); }
    70% { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
    100% { box-shadow: none; }
}

/* Aba Assinatura — layout do modelo (DM Sans style) */
#panel-assinatura {
    padding-top: 1.5rem;
    /* max-width: 860px;
    margin: 0 auto; */
}
#panel-assinatura .assinatura-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
#panel-assinatura .alert-banner {
    background: #FFFBEB;
    border: 1px solid #FCD34D;
    border-radius: 8px;
    padding: 13px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    color: #92400E;
    font-weight: 500;
}
#panel-assinatura .alert-banner .material-symbols-outlined { color: #D97706; flex-shrink: 0; font-size: 18px; }
#panel-assinatura .plan-card {
    background: var(--c-primary, #2563EB);
    border: none;
    border-radius: 14px;
    padding: 28px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    position: relative;
    overflow: hidden;
}
#panel-assinatura .plan-card::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    pointer-events: none;
}
#panel-assinatura .plan-card::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 60px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    pointer-events: none;
}
#panel-assinatura .plan-info { position: relative; z-index: 1; }
#panel-assinatura .plan-card form { position: relative; z-index: 1; }
#panel-assinatura .plan-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.65);
    margin-bottom: 6px;
}
#panel-assinatura .plan-name {
    font-size: 22px;
    font-weight: 700;
    color: white;
    margin-bottom: 10px;
}
#panel-assinatura .plan-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
#panel-assinatura .plan-badges .badge {
    background: rgba(255,255,255,0.15);
    color: white;
    font-size: 12px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 20px;
    backdrop-filter: blur(4px);
}
#panel-assinatura .plan-status-block {
    position: relative;
    z-index: 1;
    text-align: right;
    flex-shrink: 0;
}
#panel-assinatura .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.18);
    color: white;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 20px;
    margin-bottom: 10px;
    backdrop-filter: blur(4px);
}
#panel-assinatura .status-pill .dot {
    width: 7px; height: 7px;
    background: #4ADE80;
    border-radius: 50%;
    animation: assinatura-pulse 2s infinite;
}
@keyframes assinatura-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
#panel-assinatura .plan-price {
    font-size: 28px;
    font-weight: 700;
    color: white;
    font-family: ui-monospace, monospace;
}
#panel-assinatura .plan-price span { font-size: 14px; font-weight: 400; color: rgba(255,255,255,0.6); }
#panel-assinatura .info-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}
@media (max-width: 640px) {
    #panel-assinatura .info-row { grid-template-columns: 1fr; }
}
#panel-assinatura .info-tile {
    background: var(--c-surface);
    border-radius: 8px;
    border: 1px solid var(--c-border);
    padding: 18px 20px;
}
#panel-assinatura .info-tile-label {
    font-size: 11.5px;
    color: var(--c-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
}
#panel-assinatura .info-tile-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--c-text);
}
#panel-assinatura .info-tile-value.orange { color: #D97706; }
#panel-assinatura .info-tile-value.green { color: #059669; }
#panel-assinatura .card {
    background: var(--c-surface);
    border-radius: 14px;
    border: 1px solid var(--c-border);
    overflow: hidden;
}
#panel-assinatura .card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--c-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
#panel-assinatura .card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--c-muted);
}
#panel-assinatura .card-title .material-symbols-outlined { color: var(--c-primary); font-size: 16px; }
#panel-assinatura .card-action {
    font-size: 13px;
    color: var(--c-primary);
    font-weight: 500;
    text-decoration: none;
}
#panel-assinatura .card-action:hover { text-decoration: underline; }
#panel-assinatura .fatura-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    padding: 18px 24px;
    gap: 16px;
    transition: background 0.1s;
}
#panel-assinatura .fatura-row:hover { background: var(--c-soft, #F9FAFB); }
#panel-assinatura .fatura-row + .fatura-row { border-top: 1px solid var(--c-border); }
#panel-assinatura .fatura-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    min-width: 90px;
    justify-content: center;
}
#panel-assinatura .fatura-status-badge.pendente {
    background: #FFFBEB;
    color: #D97706;
}
#panel-assinatura .fatura-status-badge.pago {
    background: #ECFDF5;
    color: #059669;
}
#panel-assinatura .fatura-date {
    font-size: 14px;
    color: var(--c-muted);
    font-weight: 500;
    min-width: 110px;
    font-family: ui-monospace, monospace;
}
#panel-assinatura .fatura-desc { font-size: 13.5px; color: var(--c-muted); flex: 1; min-width: 120px; }
#panel-assinatura .fatura-amount {
    font-size: 15px;
    font-weight: 700;
    color: var(--c-text);
    font-family: ui-monospace, monospace;
    min-width: 90px;
    text-align: right;
}
#panel-assinatura .fatura-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-left: 8px;
}
#panel-assinatura .btn-assinatura {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: inherit;
    transition: all 0.15s;
    text-decoration: none;
}
#panel-assinatura .btn-assinatura.btn-primary {
    background: var(--c-primary);
    color: white;
}
#panel-assinatura .btn-assinatura.btn-primary:hover { opacity: 0.92; }
#panel-assinatura .btn-assinatura.btn-ghost {
    background: var(--c-soft, #F9FAFB);
    color: var(--c-muted);
    border: 1px solid var(--c-border);
}
#panel-assinatura .btn-assinatura.btn-ghost:hover { background: var(--c-border); color: var(--c-text); }
#panel-assinatura .btn-assinatura.btn-sm { padding: 6px 12px; font-size: 12px; }
</style>
@endpush

@section('content')
<div class="clinica-config" data-initial-tab="{{ $activeConfigTab ?? 'dados' }}">
    {{-- Tabs coladas em cima (nada acima delas) --}}
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
        @if($canAddMultiEmpresa ?? false)
        <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="empresas">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">business_center</span>
            Empresas
        </button>
        @endif
        <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="assinatura">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">payments</span>
            Assinatura
        </button>
    </div>

    {{-- Avisos da sessão sempre abaixo das tabs --}}
    @if(session('success'))
        <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>
    @endif
    @if(session('billing_warning'))
        <x-ui.alert type="warning" class="mb-5">{{ session('billing_warning') }}</x-ui.alert>
    @endif

    <form action="{{ route('clinica.configuracoes.update') }}" method="POST" enctype="multipart/form-data" id="clinica-config-form">
        @csrf
        @method('PUT')

        <div class="config-progress" id="config-progress-block">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <span style="font-size:12px;font-weight:600;color:var(--c-muted)">Perfil da empresa</span>
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
                        <label>Nome da empresa <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $clinic->name) }}" required class="form-input" placeholder="Nome da sua empresa">
                        @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>E-mail para notificações <span class="req">*</span></label>
                            <input type="email" name="notification_email" value="{{ old('notification_email', $clinic->notification_email) }}" class="form-input" placeholder="recepcao@empresa.com">
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
                        <input type="email" name="contact_email" value="{{ old('contact_email', $clinic->contact_email) }}" class="form-input" placeholder="contato@empresa.com">
                        <div class="hint">Exibido na página do Link Bio. Diferente do e-mail de notificações.</div>
                        @error('contact_email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Dados para Faturamento (obrigatório para assinatura) --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>
                    <span class="section-title">Dados para Faturamento</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--c-muted)">Necessário para assinar planos</span>
                </div>
                <div class="section-body">
                    <div class="field">
                        <label>Nome/Razão Social na nota <span class="opt">opcional</span></label>
                        <input type="text" name="billing_name" value="{{ old('billing_name', $clinic->billing_name ?? $clinic->name) }}" class="form-input" placeholder="Nome ou razão social">
                        <div class="hint">Usado em boletos e faturas. Padrão: nome da empresa.</div>
                        @error('billing_name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>E-mail para boletos <span class="opt">opcional</span></label>
                        <input type="email" name="billing_email" value="{{ old('billing_email', $clinic->billing_email ?? $clinic->notification_email) }}" class="form-input" placeholder="financeiro@empresa.com">
                        <div class="hint">Onde os boletos serão enviados. Padrão: e-mail de notificações.</div>
                        @error('billing_email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>CPF ou CNPJ <span class="req">*</span> <span class="opt">para assinatura</span></label>
                        <input type="text" name="billing_document" value="{{ old('billing_document', $clinic->billing_document) }}" class="form-input" placeholder="000.000.000-00 ou 00.000.000/0001-00" maxlength="18">
                        <div class="hint">Obrigatório para gerar boletos. Apenas números ou com pontuação.</div>
                        @error('billing_document')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Comunicação por WhatsApp --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">chat</span>
                    </div>
                    <span class="section-title">Comunicação por WhatsApp</span>
                </div>
                <div class="section-body">
                    <div class="field" style="margin-bottom:16px">
                        <label class="dark-toggle-card" style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin-bottom:0">
                            <span>
                                <span class="dtitle" style="font-size:13px;font-weight:600;color:var(--c-text)">Receber comunicação por WhatsApp</span>
                                <span class="dsub" style="display:block;font-size:11.5px;color:var(--c-muted);margin-top:2px">Confirmações e avisos da loja no número cadastrado (Telefone/WhatsApp)</span>
                            </span>
                            <div class="toggle-wrap" style="position:relative;width:44px;height:24px;flex-shrink:0">
                                <input type="checkbox" name="whatsapp_notifications_enabled" value="1" class="whatsapp-master-toggle" {{ old('whatsapp_notifications_enabled', $clinic->whatsapp_notifications_enabled) ? 'checked' : '' }} style="opacity:0;position:absolute;width:0;height:0">
                                <span class="toggle-track" style="position:absolute;inset:0;background:var(--c-border);border-radius:999px;transition:background .2s"></span>
                                <span class="toggle-thumb" style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
                            </div>
                        </label>
                    </div>
                    <div class="whatsapp-options" style="padding-left:4px;border-left:2px solid var(--c-soft);margin-top:12px">
                        <p style="font-size:12px;color:var(--c-muted);margin-bottom:10px">Quais notificações deseja receber:</p>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13px;color:var(--c-text)">
                            <input type="checkbox" name="whatsapp_notify_cobranca" value="1" {{ old('whatsapp_notify_cobranca', $clinic->whatsapp_notify_cobranca ?? true) ? 'checked' : '' }}>
                            Cobrança (lembretes, vencimentos)
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13px;color:var(--c-text)">
                            <input type="checkbox" name="whatsapp_notify_faturas_boleto" value="1" {{ old('whatsapp_notify_faturas_boleto', $clinic->whatsapp_notify_faturas_boleto ?? true) ? 'checked' : '' }}>
                            Faturas e boleto (confirmação de assinatura, boleto gerado)
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:0;cursor:pointer;font-size:13px;color:var(--c-text)">
                            <input type="checkbox" name="whatsapp_notify_avisos" value="1" {{ old('whatsapp_notify_avisos', $clinic->whatsapp_notify_avisos ?? true) ? 'checked' : '' }}>
                            Avisos gerais da loja
                        </label>
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
                    <span class="section-title">Logo da empresa</span>
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
        <div class="sticky-footer-spacer" id="config-sticky-footer-spacer" aria-hidden="true"></div>
        {{-- Sticky Footer (oculto na aba Assinatura e Empresas) --}}
        <div class="sticky-footer" id="config-sticky-footer">
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

    {{-- Painel: Empresas (fora do form principal para não submeter o form de configurações) — multi-empresa só no plano mais caro --}}
    @if($canAddMultiEmpresa ?? false)
    <div id="panel-empresas" class="config-panel" role="tabpanel" hidden>
        <div class="section-card">
            <div class="section-header">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">business_center</span>
                </div>
                <span class="section-title">Empresas do grupo</span>
                <span style="margin-left:auto;font-size:11px;color:var(--c-muted)">Disponível no plano Enterprise</span>
            </div>
            <div class="section-body">
                @if($tenantClinics->isEmpty())
                    <p style="font-size:13px;color:var(--c-muted);margin-bottom:16px">Esta é a única empresa do grupo. Adicione filiais abaixo.</p>
                @else
                    <ul style="list-style:none;padding:0;margin:0 0 20px 0">
                        @foreach($tenantClinics as $tc)
                        <li style="display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--c-border)">
                            <span class="material-symbols-outlined" style="font-size:20px;color:var(--c-muted)">business</span>
                            <span style="flex:1;font-weight:500;color:var(--c-text)">{{ $tc->name }}</span>
                            @if($tc->id === $clinic->id)
                                <span style="font-size:11px;font-weight:600;color:var(--c-primary);background:color-mix(in srgb, var(--c-primary) 12%, transparent);padding:4px 8px;border-radius:6px">Atual</span>
                            @else
                                <a href="{{ route('clinica.escolher') }}" style="font-size:12px;color:var(--c-primary)">Trocar para esta</a>
                            @endif
                            <span style="font-size:11px;color:var(--c-muted)">{{ $tc->users_count }} usuário(s)</span>
                        </li>
                        @endforeach
                    </ul>
                @endif

                <div style="border-top:1px solid var(--c-border);padding-top:20px;margin-top:8px">
                    <p style="font-size:12px;font-weight:600;color:var(--c-muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:0.05em">Adicionar empresa ou filial</p>
                    <form action="{{ route('clinica.empresas.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="field">
                            <label>Nome da empresa/filial <span class="req">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Ex: Filial Centro">
                            @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label>E-mail para notificações <span class="opt">opcional</span></label>
                            <input type="email" name="notification_email" value="{{ old('notification_email') }}" class="form-input" placeholder="filial@empresa.com">
                            <div class="hint">Se vazio, usa o mesmo da empresa atual.</div>
                            @error('notification_email')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="btn-primary">
                            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px">add</span>
                            Adicionar empresa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Painel: Assinatura (fora do form; formulários de checkout próprios) — layout modelo --}}
    <div id="panel-assinatura" class="config-panel" role="tabpanel" hidden>
        @php
            $subscriptionStatus = $clinic->subscription_status ?? 'inactive';
            $plansConfig = config('asaas.plans', []);
            $currentPlanKey = $clinic->plan_key;
            $currentPlan = $currentPlanKey && isset($plansConfig[$currentPlanKey]) ? $plansConfig[$currentPlanKey] : null;
            $currentSubscription = $billingSubscriptions->first();
            $pendingPayments = $billingPayments->filter(fn ($p) => !$p->isPaid() && !in_array($p->status, ['CANCELED'], true));
            $nextPendingDue = $pendingPayments->sortBy('due_date')->first();
            $firstPaidPayment = $billingPayments->filter(fn ($p) => $p->isPaid())->sortBy('paid_at')->first();
            // Próximo vencimento: preferir next_due_date da assinatura (banco); senão primeira fatura pendente
            $proximoVencimento = $currentSubscription?->next_due_date ?? $nextPendingDue?->due_date;
            $proximoVencimentoPendente = (bool) $nextPendingDue;
        @endphp

        @if(in_array($clinic->billing_status, ['blocked'], true) || in_array($subscriptionStatus, ['inactive', 'canceled'], true))
        <div role="alert" class="config-page-alert config-page-alert-error" style="margin-bottom:1rem">
            <span class="material-symbols-outlined" style="font-size:18px;flex-shrink:0">cancel</span>
            <span>Acesso suspenso. Regularize a assinatura para continuar.</span>
        </div>
        @endif

        <div class="assinatura-main">
            @if($pendingPayments->isNotEmpty() && $nextPendingDue)
            <div class="alert-banner" role="alert">
                <span class="material-symbols-outlined">info</span>
                Você possui {{ $pendingPayments->count() }} fatura(s) pendente(s) com vencimento em {{ $nextPendingDue->due_date->format('d/m/Y') }}. Regularize para evitar suspensão do serviço.
            </div>
            @endif

            @if($currentPlan && in_array($subscriptionStatus, ['active', 'trial', 'past_due'], true))
            {{-- Card do plano ativo --}}
            <div class="plan-card">
                <div class="plan-info">
                    <div class="plan-label">Plano Atual</div>
                    <div class="plan-name">{{ $currentPlan['name'] ?? $currentPlanKey }} {{ $subscriptionStatus === 'trial' ? '(Trial)' : '' }}</div>
                    <div class="plan-badges">
                        <span class="badge">✦ Página pública</span>
                        <span class="badge">✦ Tema personalizado</span>
                        <span class="badge">✦ Suporte prioritário</span>
                    </div>
                </div>
                <div class="plan-status-block">
                    <div>
                        <div class="status-pill"><span class="dot"></span>
                            @if($subscriptionStatus === 'trial') Trial
                            @elseif($subscriptionStatus === 'past_due') Pagamento pendente
                            @else Ativo
                            @endif
                        </div>
                    </div>
                    <div class="plan-price">R$ {{ number_format($currentPlan['value'] ?? 0, 2, ',', '.') }}<span>/mês</span></div>
                </div>
            </div>

            {{-- Tiles de info --}}
            <div class="info-row">
                <div class="info-tile">
                    <div class="info-tile-label">Início da assinatura</div>
                    <div class="info-tile-value">{{ $firstPaidPayment?->paid_at?->format('d/m/Y') ?? $firstPaidPayment?->due_date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div class="info-tile">
                    <div class="info-tile-label">Próximo vencimento</div>
                    <div class="info-tile-value {{ $proximoVencimentoPendente ? 'orange' : '' }}">{{ $proximoVencimento ? $proximoVencimento->format('d/m/Y') : '—' }}</div>
                </div>
                <div class="info-tile">
                    <div class="info-tile-label">Forma de pagamento</div>
                    <div class="info-tile-value">Boleto bancário</div>
                </div>
            </div>
            @endif

            {{-- Lista de planos (quando não ativo / sem plano) --}}
            @if(!$currentPlan || !in_array($subscriptionStatus, ['active', 'trial', 'past_due'], true))
            <div class="section-card" id="planos-assinatura">
                <div class="section-header">
                    <div class="icon-wrap"><span class="material-symbols-outlined">subscriptions</span></div>
                    <div>
                        <span class="section-title">Planos</span>
                        <p style="font-size:13px;color:var(--c-muted);margin-top:2px">Assine durante o trial — sua cobrança inicia na confirmação.</p>
                    </div>
                </div>
                <div class="section-body">
                    @if(!$asaasConfigured)
                        <p style="font-size:13px;color:var(--c-muted)">Pagamentos não estão configurados. Entre em contato com o suporte.</p>
                    @else
                        <div class="plan-grid">
                            @foreach($billingPlans as $key => $plan)
                                <div class="plan-card" style="background:var(--c-surface);border:1px solid var(--c-border);color:var(--c-text);">
                                    <div class="plan-info">
                                        <div class="plan-label" style="color:var(--c-muted)">Plano</div>
                                        <div class="plan-name" style="color:var(--c-text)">{{ $plan['name'] ?? $key }}</div>
                                        <div class="plan-price" style="font-size:1.25rem;color:var(--c-primary)">R$ {{ number_format($plan['value'] ?? 0, 2, ',', '.') }} <span style="color:var(--c-muted)">/mês</span></div>
                                    </div>
                                    <form method="post" action="{{ route('billing.checkout') }}">
                                        @csrf
                                        <input type="hidden" name="plan_key" value="{{ $key }}">
                                        <button type="submit" class="btn-regularize">Assinar {{ $plan['name'] ?? $key }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if(in_array($clinic->billing_status, ['blocked', 'attention'], true) || in_array($subscriptionStatus, ['inactive', 'past_due', 'canceled'], true))
            <p style="margin-bottom:12px">
                <a href="#planos-assinatura" id="btn-regularizar-agora" class="btn-regularize">
                    <span class="material-symbols-outlined">payments</span>
                    Regularizar agora
                </a>
            </p>
            @endif

            {{-- Boletos e Faturas (card estilo modelo) --}}
            <div class="card" id="boletos-faturas-section">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">description</span>
                        Boletos e Faturas
                    </div>
                    @if(Route::has('billing.index'))
                    <a href="{{ route('billing.index') }}" class="card-action">Ver todas</a>
                    @endif
                </div>
                @if($billingPayments->isEmpty())
                <div style="padding:24px;font-size:13px;color:var(--c-muted)">Nenhum boleto ou fatura até o momento.</div>
                @else
                    @php $statusLabels = ['PENDING' => 'Pendente', 'OVERDUE' => 'Vencido', 'RECEIVED' => 'Pago', 'CONFIRMED' => 'Pago', 'RECEIVED_IN_CASH' => 'Pago', 'CANCELED' => 'Cancelado']; @endphp
                    @foreach($billingPayments as $p)
                    <div class="fatura-row">
                        <div class="fatura-date">{{ $p->due_date?->format('d/m/Y') ?? '—' }}</div>
                        @php $isPaid = $p->isPaid(); @endphp
                        <span class="fatura-status-badge {{ $isPaid ? 'pago' : 'pendente' }}">
                            @if($isPaid)✔ Pago @else ⚠ {{ $statusLabels[$p->status] ?? $p->status }} @endif
                        </span>
                        <div class="fatura-desc">Plano {{ $currentPlan['name'] ?? $currentPlanKey ?? 'Assinatura' }} — {{ $p->due_date?->translatedFormat('F Y') ?? '—' }}</div>
                        <div class="fatura-amount">R$ {{ number_format($p->value ?? 0, 2, ',', '.') }}</div>
                        <div class="fatura-actions">
                            @if($p->bank_slip_url)
                                <a href="{{ $p->bank_slip_url }}" target="_blank" rel="noopener noreferrer" class="btn-assinatura btn-primary btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:13px">visibility</span>
                                    Ver boleto
                                </a>
                                <a href="{{ $p->bank_slip_url }}" target="_blank" rel="noopener noreferrer" download class="btn-assinatura btn-ghost btn-sm">
                                    <span class="material-symbols-outlined" style="font-size:13px">download</span>
                                    Baixar
                                </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var wrapper = document.querySelector('.clinica-config');
    var form = document.getElementById('clinica-config-form');
    if (!wrapper || !form) return;

    var footerEl = document.getElementById('config-sticky-footer');

    function setTab(tabId) {
        wrapper.querySelectorAll('.config-tab').forEach(function(b) {
            var isActive = b.getAttribute('data-tab') === tabId;
            b.classList.toggle('active', isActive);
            b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        wrapper.querySelectorAll('.config-panel').forEach(function(p) {
            var pid = p.id;
            var match = pid && pid === 'panel-' + tabId;
            p.classList.toggle('active', match);
            p.hidden = !match;
        });
        var hideFooter = (tabId === 'assinatura' || tabId === 'empresas');
        if (footerEl) footerEl.style.display = hideFooter ? 'none' : 'flex';
        var progressEl = document.getElementById('config-progress-block');
        if (progressEl) progressEl.style.display = hideFooter ? 'none' : 'block';
        var spacerEl = document.getElementById('config-sticky-footer-spacer');
        if (spacerEl) spacerEl.style.display = hideFooter ? 'none' : 'block';
    }

    var initialTab = wrapper.getAttribute('data-initial-tab') || 'dados';
    if (initialTab !== 'dados') setTab(initialTab);

    function scrollToPlanos() {
        var el = document.getElementById('planos-assinatura');
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('highlight-scroll');
        setTimeout(function() { el.classList.remove('highlight-scroll'); }, 2000);
    }

    if (window.location.hash === '#planos-assinatura') {
        setTab('assinatura');
        setTimeout(scrollToPlanos, 100);
    }

    // Botão "Regularizar agora": fase de captura para rodar antes de qualquer outro listener
    document.addEventListener('click', function(e) {
        var link = e.target.closest('#btn-regularizar-agora');
        if (!link) return;
        e.preventDefault();
        e.stopPropagation();
        setTab('assinatura');
        setTimeout(scrollToPlanos, 150);
    }, true);

    var btnRegularizar = document.getElementById('btn-regularizar-agora');
    if (btnRegularizar) {
        btnRegularizar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            setTab('assinatura');
            setTimeout(scrollToPlanos, 150);
        }, true);
    }

    wrapper.querySelectorAll('.config-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTab(this.getAttribute('data-tab'));
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

    var copyFirstBtn = document.getElementById('copyFirstToAll');
    if (copyFirstBtn) copyFirstBtn.addEventListener('click', function() {
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

    // WhatsApp master toggle: sync visual e habilitar/desabilitar opções
    var whatsappMaster = form.querySelector('.whatsapp-master-toggle');
    var whatsappOpts = form.querySelectorAll('.whatsapp-options input[type="checkbox"]');
    var whatsappCard = whatsappMaster ? whatsappMaster.closest('.section-card') : null;
    var whatsappTrack = whatsappCard ? whatsappCard.querySelector('.toggle-track') : null;
    var whatsappThumb = whatsappCard ? whatsappCard.querySelector('.toggle-thumb') : null;
    function syncWhatsAppToggle(checked) {
        if (whatsappTrack) whatsappTrack.style.background = checked ? 'var(--c-primary)' : 'var(--c-border)';
        if (whatsappThumb) whatsappThumb.style.transform = checked ? 'translateX(20px)' : 'translateX(0)';
        whatsappOpts.forEach(function(cb) { cb.disabled = !checked; });
    }
    if (whatsappMaster) {
        syncWhatsAppToggle(whatsappMaster.checked);
        whatsappMaster.addEventListener('change', function() { syncWhatsAppToggle(this.checked); });
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
