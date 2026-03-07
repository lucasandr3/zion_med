@extends('layouts.app')

@section('title', 'Integrações')

@push('styles')
<style>
/* Mesmo padrão da página Configurações da clínica */
.integracoes-page {
    display: flex;
    flex-direction: column;
    min-height: 0;
    width: 100%;
}
.integracoes-page .config-tabs {
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
    .integracoes-page .config-tabs { margin-left: -1.5rem; margin-right: -1.5rem; padding-left: 1.5rem; padding-right: 1.5rem; }
}
@media (min-width: 1024px) {
    .integracoes-page .config-tabs { margin-left: -2rem; margin-right: -2rem; margin-top: -2rem; padding-left: 2rem; padding-right: 2rem; }
}
.integracoes-page .config-tab {
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
.integracoes-page .config-tab:hover { color: var(--c-text); }
.integracoes-page .config-tab.active { color: var(--c-primary); border-bottom-color: var(--c-primary); }

.integracoes-page .config-panel { display: none;margin-top: 1rem; }
.integracoes-page .config-panel.active { display: block; }

.integracoes-page .section-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
}
.integracoes-page .section-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--c-border);
    display: flex;
    align-items: center;
    gap: 10px;
}
.integracoes-page .section-header .icon-wrap {
    width: 28px;
    height: 28px;
    background: color-mix(in srgb, var(--c-primary) 12%, transparent);
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.integracoes-page .section-header .icon-wrap .material-symbols-outlined {
    font-size: 14px;
    color: var(--c-primary);
}
.integracoes-page .section-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--c-muted);
}
.integracoes-page .section-body { padding: 20px; }
.integracoes-page .field { margin-bottom: 18px; }
.integracoes-page .field:last-child { margin-bottom: 0; }
.integracoes-page .field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--c-text);
    margin-bottom: 6px;
}
.integracoes-page .field .hint { font-size: 11px; color: var(--c-muted); margin-top: 5px; word-break: break-word; overflow-wrap: break-word; }
.integracoes-page .section-body p { font-size: 13px; color: var(--c-muted); word-break: break-word; overflow-wrap: break-word; }
.integracoes-page .section-body code { font-size: 12px; word-break: break-all; }
</style>
@endpush

@section('content')
<div class="integracoes-page">
    @if(session('success'))
        <p class="mb-4 p-3 rounded-lg" style="background:color-mix(in srgb, var(--c-primary) 15%, transparent);color:var(--c-primary);font-size:0.875rem">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="mb-4 p-3 rounded-lg" style="background:#fef2f2;color:#b91c1c;font-size:0.875rem">{{ session('error') }}</p>
    @endif

    {{-- Abas (mesmo padrão da página Configurações) --}}
    <div class="config-tabs" role="tablist">
        <button type="button" class="config-tab active" role="tab" aria-selected="true" data-tab="api">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">key</span>
            API e tokens
        </button>
        <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="webhooks">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">webhook</span>
            Webhooks
        </button>
        <button type="button" class="config-tab" role="tab" aria-selected="false" data-tab="entregas">
            <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px">history</span>
            Entregas
        </button>
    </div>

    {{-- Painel: API e tokens --}}
    <div id="panel-api" class="config-panel active" role="tabpanel">
        <div class="section-card" style="background:color-mix(in srgb, var(--c-primary) 8%, transparent);border-color:var(--c-primary)">
            <div class="section-header">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">menu_book</span>
                </div>
                <span class="section-title">Documentação da API</span>
            </div>
            <div class="section-body">
                <p style="margin-bottom:12px">Documentação interativa (Scramble) e especificação OpenAPI para integrar com outros sistemas.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a href="{{ url('/docs/api') }}" target="_blank" rel="noopener" class="btn-primary" style="padding:8px 14px;font-size:0.8rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <span class="material-symbols-outlined" style="font-size:16px">menu_book</span>
                        Documentação interativa
                    </a>
                    <a href="{{ url('/docs/api.json') }}" target="_blank" rel="noopener" class="btn-ghost" style="padding:8px 14px;font-size:0.8rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <span class="material-symbols-outlined" style="font-size:16px">download</span>
                        OpenAPI (JSON)
                    </a>
                </div>
            </div>
        </div>

        @if(session('new_token_plain'))
            <div class="section-card" style="border-color:var(--c-primary)">
                <div class="section-header">
                    <div class="icon-wrap">
                        <span class="material-symbols-outlined">key</span>
                    </div>
                    <span class="section-title">Token criado: {{ session('new_token_name') }}</span>
                </div>
                <div class="section-body">
                    <p class="hint" style="margin-bottom:10px">Copie e guarde em local seguro. Este valor não será exibido novamente.</p>
                    <code id="new-token" style="display:block;padding:12px;background:var(--c-soft);border-radius:8px;word-break:break-all;font-size:0.8rem">{{ session('new_token_plain') }}</code>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-token').textContent);this.textContent='Copiado!'" class="btn-ghost mt-2" style="padding:6px 12px;font-size:0.8rem">Copiar</button>
                </div>
            </div>
        @endif

        <div class="section-card">
            <div class="section-header">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">key</span>
                </div>
                <span class="section-title">Token de API</span>
            </div>
            <div class="section-body">
                <p style="margin-bottom:8px">Gere um token para acessar a API REST (protocolos, templates).</p>
                <p class="hint" style="margin-bottom:16px">Use o header: <code style="display:inline-block;margin-top:4px">Authorization: Bearer SEU_TOKEN</code></p>
                <form action="{{ route('clinica.integracoes.tokens.store') }}" method="POST" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                    @csrf
                    <div class="field" style="margin-bottom:0">
                        <label>Nome do token</label>
                        <input type="text" name="name" required placeholder="Ex: ERP, Sistema externo" class="form-input" style="min-width:200px" value="{{ old('name') }}">
                        @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary" style="padding:10px 16px">
                        <span class="material-symbols-outlined" style="font-size:18px">add</span>
                        Criar token
                    </button>
                </form>
                @if($tokens->isNotEmpty())
                    <table class="mt-4" style="width:100%;font-size:0.8rem;border-collapse:collapse">
                        <thead>
                            <tr style="border-bottom:1px solid var(--c-border);text-align:left">
                                <th style="padding:8px 0">Nome</th>
                                <th style="padding:8px 0">Últimos caracteres</th>
                                <th style="padding:8px 0">Criado em</th>
                                <th style="padding:8px 0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tokens as $t)
                                <tr style="border-bottom:1px solid var(--c-border)">
                                    <td style="padding:8px 0">{{ Str::after($t->name, 'clinic:' . session('current_clinic_id') . '-') }}</td>
                                    <td style="padding:8px 0;color:var(--c-muted)">••••••••</td>
                                    <td style="padding:8px 0;color:var(--c-muted)">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                    <td style="padding:8px 0">
                                        <form action="{{ route('clinica.integracoes.tokens.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Revogar este token?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="color:var(--c-muted);font-size:0.75rem;background:none;border:none;cursor:pointer;text-decoration:underline">Revogar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Painel: Webhooks --}}
    <div id="panel-webhooks" class="config-panel" role="tabpanel" hidden>
        <div class="section-card">
            <div class="section-header">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">webhook</span>
                </div>
                <span class="section-title">Webhooks</span>
            </div>
            <div class="section-body">
                <p style="margin-bottom:8px">Receba notificações em tempo real (POST na URL informada) quando uma submissão for criada, assinada, aprovada ou reprovada.</p>
                <p class="hint" style="margin-bottom:16px">Cada requisição inclui o header <code>X-Webhook-Signature</code> com assinatura HMAC SHA-256 do corpo (formato: <code>sha256=&lt;hash&gt;</code>). Use o secret configurado para validar a autenticidade no seu servidor.</p>
                <form action="{{ route('clinica.integracoes.webhooks.store') }}" method="POST" style="display:flex;flex-direction:column;gap:1rem;max-width:560px;margin-bottom:1.5rem">
                    @csrf
                    <div class="field" style="margin-bottom:0">
                        <label>URL</label>
                        <input type="url" name="url" required placeholder="https://seu-sistema.com/webhook" class="form-input" value="{{ old('url') }}">
                        @error('url')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label>Eventos</label>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px">
                            @foreach($availableEvents as $ev)
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.8rem;cursor:pointer">
                                    <input type="checkbox" name="events[]" value="{{ $ev }}" {{ in_array($ev, old('events', [])) ? 'checked' : '' }}>
                                    {{ $eventLabels[$ev] ?? $ev }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label>Secret <span class="opt" style="font-size:10px;font-weight:400;color:var(--c-muted);margin-left:6px">opcional, para assinatura HMAC</span></label>
                        <input type="text" name="secret" placeholder="Chave secreta" class="form-input" style="max-width:280px" value="{{ old('secret') }}">
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label>Descrição <span class="opt" style="font-size:10px;font-weight:400;color:var(--c-muted);margin-left:6px">opcional</span></label>
                        <input type="text" name="description" placeholder="Ex: ERP principal" class="form-input" style="max-width:280px" value="{{ old('description') }}">
                    </div>
                    <button type="submit" class="btn-primary" style="align-self:flex-start;padding:10px 16px">
                        <span class="material-symbols-outlined" style="font-size:18px">add</span>
                        Adicionar webhook
                    </button>
                </form>
                @if($webhooks->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:0.75rem">
                        @foreach($webhooks as $wh)
                            <div style="padding:12px;border:1px solid var(--c-border);border-radius:8px;font-size:0.8rem">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                                    <div>
                                        <strong>{{ $wh->description ?: 'Webhook' }}</strong>
                                        <span style="color:var(--c-muted)"> — {{ $wh->url }}</span>
                                        @if(!$wh->is_active)<span style="color:var(--c-muted)"> (inativo)</span>@endif
                                        <div style="margin-top:4px;color:var(--c-muted)">
                                            @foreach($wh->events ?? [] as $ev)
                                                {{ $eventLabels[$ev] ?? $ev }}@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:8px">
                                        <form action="{{ route('clinica.integracoes.webhooks.destroy', $wh) }}" method="POST" onsubmit="return confirm('Remover este webhook?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="color:#f87171;font-size:0.75rem;background:none;border:none;cursor:pointer;text-decoration:underline">Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Painel: Entregas --}}
    <div id="panel-entregas" class="config-panel" role="tabpanel" hidden>
        <div class="section-card">
            <div class="section-header">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">history</span>
                </div>
                <span class="section-title">Últimas entregas de webhook</span>
            </div>
            <div class="section-body">
                @if($deliveries->isEmpty())
                    <p style="font-size:0.8rem;color:var(--c-muted)">Nenhuma entrega registrada.</p>
                @else
                    <div style="overflow-x:auto">
                        <table style="width:100%;font-size:0.75rem;border-collapse:collapse">
                            <thead>
                                <tr style="border-bottom:1px solid var(--c-border);text-align:left">
                                    <th style="padding:6px 8px">Data</th>
                                    <th style="padding:6px 8px">Evento</th>
                                    <th style="padding:6px 8px">URL</th>
                                    <th style="padding:6px 8px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deliveries as $d)
                                    <tr style="border-bottom:1px solid var(--c-border)">
                                        <td style="padding:6px 8px;color:var(--c-muted)">{{ $d->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td style="padding:6px 8px">{{ $eventLabels[$d->event] ?? $d->event }}</td>
                                        <td style="padding:6px 8px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $d->clinicWebhook->url }}">{{ $d->clinicWebhook->url }}</td>
                                        <td style="padding:6px 8px">
                                            @if($d->response_code >= 200 && $d->response_code < 300)
                                                <span style="color:#22c55e">{{ $d->response_code }}</span>
                                            @elseif($d->error_message)
                                                <span style="color:#f87171" title="{{ $d->error_message }}">Erro</span>
                                            @else
                                                <span style="color:var(--c-muted)">{{ $d->response_code ?? '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var container = document.querySelector('.integracoes-page');
    if (!container) return;
    container.querySelectorAll('.config-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            container.querySelectorAll('.config-tab').forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            container.querySelectorAll('.config-panel').forEach(function(p) {
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
})();
</script>
@endsection
