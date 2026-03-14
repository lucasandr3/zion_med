@extends('layouts.platform')

@section('title', 'Configurações da plataforma')
@section('subtitle', 'Parâmetros editáveis (banco). API e URL continuam no .env.')

@section('content')
    <div class="w-full space-y-4">
        {{-- Somente leitura: API e URL (vêm do .env) --}}
        <div class="card">
            <h3 class="text-xs font-semibold tracking-[0.1em] uppercase mb-3" style="color:var(--c-muted)">Asaas — ambiente (.env)</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt style="color:var(--c-muted)">URL da API</dt>
                    <dd class="font-mono text-xs truncate max-w-[240px]" style="color:var(--c-text)" title="{{ $baseUrl ?? '' }}">{{ $baseUrl ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt style="color:var(--c-muted)">API configurada</dt>
                    <dd class="font-medium" style="color:var(--c-text)">{{ $apiConfigured ? 'Sim' : 'Não' }}</dd>
                </div>
            </dl>
            <p class="text-xs mt-3" style="color:var(--c-muted)">Para alterar base URL e chave da API, edite o <code class="px-1 rounded" style="background:var(--c-soft);color:var(--c-text)">.env</code>.</p>
        </div>

        <form action="{{ route('platform.settings.update') }}" method="post" class="card space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-xs font-semibold tracking-[0.1em] uppercase mb-3" style="color:var(--c-muted)">Parâmetros da plataforma (banco)</h3>

            <div>
                <label for="product_name" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Nome do produto</label>
                <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $productName) }}"
                       class="form-input w-full max-w-md" required maxlength="128"
                       placeholder="Ex: ZionMed">
                @error('product_name')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-6">
                <div>
                    <label for="trial_days" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Trial (dias)</label>
                    <input type="number" name="trial_days" id="trial_days" value="{{ old('trial_days', $trialDays) }}"
                           class="form-input w-24" min="0" max="365" required
                           placeholder="14">
                    @error('trial_days')
                        <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="grace_days" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Grace (dias)</label>
                    <input type="number" name="grace_days" id="grace_days" value="{{ old('grace_days', $graceDays) }}"
                           class="form-input w-24" min="0" max="90" required
                           placeholder="7">
                    @error('grace_days')
                        <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="block_mode" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Modo de bloqueio</label>
                <select name="block_mode" id="block_mode" class="form-select w-40">
                    <option value="soft" {{ old('block_mode', $blockMode) === 'soft' ? 'selected' : '' }}>soft</option>
                    <option value="hard" {{ old('block_mode', $blockMode) === 'hard' ? 'selected' : '' }}>hard</option>
                </select>
                <p class="text-xs mt-1" style="color:var(--c-muted)">soft = bloqueia app e libera /billing; hard = bloqueia tudo exceto logout.</p>
                @error('block_mode')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="multi_empresa_plan" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Plano multi-empresa (chave do plano)</label>
                <input type="text" name="multi_empresa_plan" id="multi_empresa_plan" value="{{ old('multi_empresa_plan', $multiEmpresaPlan) }}"
                       class="form-input w-48" maxlength="64" required placeholder="ex: enterprise">
                @error('multi_empresa_plan')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary">Salvar configurações</button>
        </form>

        {{-- Status do serviço (exibido em /status) --}}
        <form action="{{ route('platform.status.update') }}" method="post" class="card space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-xs font-semibold tracking-[0.1em] uppercase mb-3" style="color:var(--c-muted)">Status do serviço (/status)</h3>
            <p class="text-xs" style="color:var(--c-muted)">O status, criticidade e componentes são exibidos na página pública em <a href="{{ route('status') }}" target="_blank" rel="noopener" class="underline" style="color:var(--c-primary)">{{ url('/status') }}</a> e no banner da landing page.</p>

            @if(session('success_status'))
                <p class="text-xs rounded px-3 py-2" style="background:var(--c-soft);color:var(--c-text)">{{ session('success_status') }}</p>
            @endif

            <div class="flex flex-wrap gap-6">
                <div>
                    <label for="service_status" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Status geral</label>
                    <select name="status" id="service_status" class="form-select w-48">
                        <option value="operational" {{ old('status', $serviceStatus) === 'operational' ? 'selected' : '' }}>Operacional</option>
                        <option value="degraded" {{ old('status', $serviceStatus) === 'degraded' ? 'selected' : '' }}>Degradado</option>
                        <option value="outage" {{ old('status', $serviceStatus) === 'outage' ? 'selected' : '' }}>Indisponível</option>
                        <option value="maintenance" {{ old('status', $serviceStatus) === 'maintenance' ? 'selected' : '' }}>Manutenção</option>
                    </select>
                    @error('status')
                        <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="service_severity" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Criticidade</label>
                    <select name="severity" id="service_severity" class="form-select w-40">
                        <option value="none" {{ old('severity', $serviceStatusSeverity) === 'none' ? 'selected' : '' }}>Nenhuma</option>
                        <option value="low" {{ old('severity', $serviceStatusSeverity) === 'low' ? 'selected' : '' }}>Baixa</option>
                        <option value="medium" {{ old('severity', $serviceStatusSeverity) === 'medium' ? 'selected' : '' }}>Média</option>
                        <option value="high" {{ old('severity', $serviceStatusSeverity) === 'high' ? 'selected' : '' }}>Alta</option>
                        <option value="critical" {{ old('severity', $serviceStatusSeverity) === 'critical' ? 'selected' : '' }}>Crítica</option>
                    </select>
                    @error('severity')
                        <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="service_status_message" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Mensagem (opcional)</label>
                <textarea name="message" id="service_status_message" rows="2" class="form-input w-full max-w-md" maxlength="500" placeholder="Ex: Manutenção programada amanhã 2h–4h">{{ old('message', $serviceStatusMessage) }}</textarea>
                @error('message')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <p class="block text-xs font-medium mb-2" style="color:var(--c-text)">Componentes</p>
                <div class="space-y-2">
                    @foreach($componentOptions as $compKey => $compLabel)
                        <div class="flex items-center gap-3">
                            <span class="text-xs w-40" style="color:var(--c-text)">{{ $compLabel }}</span>
                            <select name="components[{{ $compKey }}]" class="form-select text-xs w-40">
                                @php $compVal = old("components.$compKey", $serviceComponents[$compKey] ?? 'operational'); @endphp
                                <option value="operational" {{ $compVal === 'operational' ? 'selected' : '' }}>Operacional</option>
                                <option value="degraded" {{ $compVal === 'degraded' ? 'selected' : '' }}>Degradado</option>
                                <option value="outage" {{ $compVal === 'outage' ? 'selected' : '' }}>Indisponível</option>
                                <option value="maintenance" {{ $compVal === 'maintenance' ? 'selected' : '' }}>Manutenção</option>
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn-primary">Atualizar status</button>
        </form>
    </div>
@endsection
