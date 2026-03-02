@extends('layouts.app')

@section('title', 'Assinatura e pagamentos')

@push('styles')
<style>
.billing-page { display: flex; flex-direction: column; min-height: 0; width: 100%; }
.billing-page .section-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}
.billing-page .section-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--c-border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.billing-page .section-header .icon-wrap {
    width: 28px; height: 28px;
    background: color-mix(in srgb, var(--c-primary) 12%, transparent);
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
}
.billing-page .section-header .icon-wrap .material-symbols-outlined { font-size: 14px; color: var(--c-primary); }
.billing-page .section-title {
    font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--c-muted);
}
.billing-page .section-body { padding: 20px; }
.billing-page .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}
.billing-page .status-badge.trial { background: rgba(59,130,246,0.12); color: #2563eb; }
.billing-page .status-badge.active { background: rgba(34,197,94,0.12); color: #16a34a; }
.billing-page .status-badge.past_due { background: rgba(234,179,8,0.12); color: #ca8a04; }
.billing-page .status-badge.blocked { background: rgba(239,68,68,0.12); color: #dc2626; }
.billing-page .status-badge.inactive { background: var(--c-soft); color: var(--c-muted); }
.billing-page .plan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.billing-page .plan-card {
    border: 1px solid var(--c-border);
    border-radius: 12px;
    padding: 20px;
    background: var(--c-surface);
    transition: border-color 0.15s;
}
.billing-page .plan-card:hover { border-color: var(--c-primary); }
.billing-page .plan-card .plan-name { font-size: 1rem; font-weight: 700; color: var(--c-text); margin-bottom: 4px; }
.billing-page .plan-card .plan-desc { font-size: 12px; color: var(--c-muted); margin-bottom: 12px; }
.billing-page .plan-card .plan-value { font-size: 1.25rem; font-weight: 700; color: var(--c-primary); margin-bottom: 14px; }
.billing-page .plan-card .plan-value small { font-size: 0.75rem; font-weight: 500; color: var(--c-muted); }
.billing-page .btn-regularize {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    background: var(--c-primary);
    color: #fff;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-family: inherit;
}
.billing-page .btn-regularize:hover { opacity: 0.92; }
.billing-page .btn-regularize .material-symbols-outlined { font-size: 18px; }
.billing-page .meta-line { font-size: 13px; color: var(--c-muted); margin-top: 8px; }
.billing-page .meta-line strong { color: var(--c-text); }
#planos.highlight-scroll { animation: planos-highlight 0.6s ease-out; }
@keyframes planos-highlight {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.45); }
    70% { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
    100% { box-shadow: none; }
}
</style>
@endpush

@section('content')
<div class="billing-page">
    {{-- Status atual --}}
    <div class="section-card">
        <div class="section-header">
            <div class="icon-wrap"><span class="material-symbols-outlined">payments</span></div>
            <div>
                <p class="section-title">Status da assinatura</p>
                <p class="mt-1" style="font-size:14px;color:var(--c-text)">
                    @php
                        $status = $clinic->subscription_status ?? 'inactive';
                        $labels = [
                            'trial' => 'Trial',
                            'active' => 'Ativo',
                            'past_due' => 'Pagamento pendente',
                            'canceled' => 'Cancelado',
                            'inactive' => 'Inativo / Suspenso',
                        ];
                        $label = $labels[$status] ?? $status;
                    @endphp
                    <span class="status-badge {{ $status }}">{{ $label }}</span>
                </p>
            </div>
        </div>
        <div class="section-body">
            @if($clinic->subscription_status === 'trial' && $clinic->trial_ends_at)
                <p class="meta-line">Trial ativo até <strong>{{ $clinic->trial_ends_at->format('d/m/Y') }}</strong></p>
            @endif
            @if($clinic->subscription_status === 'past_due' && $clinic->grace_ends_at)
                <p class="meta-line">Regularize até <strong>{{ $clinic->grace_ends_at->format('d/m/Y') }}</strong> para evitar a suspensão.</p>
            @endif
            @if(in_array($clinic->billing_status, ['blocked', 'attention'], true) || in_array($clinic->subscription_status, ['inactive', 'past_due', 'canceled'], true))
                <a href="#planos" id="btn-regularizar-agora" class="btn-regularize">
                    <span class="material-symbols-outlined">payments</span>
                    Regularizar agora
                </a>
            @endif
        </div>
    </div>

    {{-- Planos e checkout --}}
    <div class="section-card" id="planos">
        <div class="section-header">
            <div class="icon-wrap"><span class="material-symbols-outlined">subscriptions</span></div>
            <div>
                <p class="section-title">Planos</p>
                <p style="font-size:13px;color:var(--c-muted);margin-top:2px">Assine durante o trial — sua cobrança inicia na confirmação.</p>
            </div>
        </div>
        <div class="section-body">
            @if(!$asaasConfigured)
                <p style="font-size:13px;color:var(--c-muted)">Pagamentos não estão configurados. Entre em contato com o suporte.</p>
            @else
                <div class="plan-grid">
                    @foreach($plans as $key => $plan)
                        <div class="plan-card">
                            <p class="plan-name">{{ $plan['name'] ?? $key }}</p>
                            <p class="plan-desc">{{ $plan['description'] ?? '' }}</p>
                            <p class="plan-value">R$ {{ number_format($plan['value'] ?? 0, 2, ',', '.') }} <small>/mês</small></p>
                            <form method="post" action="{{ route('billing.checkout') }}" class="inline">
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

    {{-- Últimos pagamentos (opcional) --}}
    @if($payments->isNotEmpty())
    <div class="section-card">
        <div class="section-header">
            <div class="icon-wrap"><span class="material-symbols-outlined">receipt_long</span></div>
            <div>
                <p class="section-title">Últimos pagamentos</p>
            </div>
        </div>
        <div class="section-body">
            <ul style="list-style:none;padding:0;margin:0">
                @foreach($payments as $p)
                    <li style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-border);font-size:13px">
                        <span style="color:var(--c-text)">{{ $p->due_date?->format('d/m/Y') ?? '-' }}</span>
                        <span style="color:var(--c-muted)">{{ $p->status }}</span>
                        <span style="color:var(--c-text)">R$ {{ number_format($p->value ?? 0, 2, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
<script>
(function() {
    var link = document.getElementById('btn-regularizar-agora');
    var planos = document.getElementById('planos');
    if (!link || !planos) return;
    link.addEventListener('click', function(e) {
        e.preventDefault();
        planos.scrollIntoView({ behavior: 'smooth', block: 'start' });
        planos.classList.add('highlight-scroll');
        setTimeout(function() { planos.classList.remove('highlight-scroll'); }, 2000);
    });
})();
</script>
@endsection
