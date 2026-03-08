@extends('layouts.platform')

@section('title', $plan ? 'Editar plano' : 'Novo plano')
@section('subtitle', $plan ? $plan->name : 'Criar plano de assinatura.')

@section('content')
    <div class="w-full max-w-4xl">
        <form action="{{ $plan ? route('platform.plans.update', $plan) : route('platform.plans.store') }}" method="post" class="card space-y-4">
            @csrf
            @if($plan)
                @method('PUT')
            @endif

            <div>
                <label for="key" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Chave (única, ex: core, enterprise)</label>
                <input type="text" name="key" id="key" value="{{ old('key', $plan?->key) }}"
                       class="form-input" pattern="[a-z0-9_-]+" maxlength="64" required
                       placeholder="Ex: core, enterprise"
                       {{ $plan ? 'readonly' : '' }}>
                @if($plan)
                    <p class="text-xs mt-1" style="color:var(--c-muted)">A chave não pode ser alterada após criação.</p>
                @endif
                @error('key')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Nome</label>
                <input type="text" name="name" id="name" value="{{ old('name', $plan?->name) }}"
                       class="form-input" maxlength="128" required
                       placeholder="Ex: Core">
                @error('name')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="value_display" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Valor (R$/mês)</label>
                <input type="text" id="value_display" class="form-input w-40" placeholder="0,00" inputmode="decimal" autocomplete="off">
                <input type="hidden" name="value" id="value" value="{{ old('value', $plan?->value) }}">
                @error('value')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Descrição</label>
                <textarea name="description" id="description" rows="3" class="form-input" maxlength="500" placeholder="Ex: Para clínicas pequenas e médias que precisam padronizar formulários...">{{ old('description', $plan?->description) }}</textarea>
                @error('description')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sort_order" class="block text-xs font-medium mb-1" style="color:var(--c-text)">Ordem de exibição</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $sortOrder ?? 0) }}"
                       class="form-input w-24" min="0" placeholder="0">
                @error('sort_order')
                    <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm" style="color:var(--c-text)">Plano ativo (disponível para assinatura)</label>
            </div>
            @error('is_active')
                <p class="text-xs mt-1" style="color:var(--c-primary)">{{ $message }}</p>
            @enderror

            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary">{{ $plan ? 'Salvar' : 'Criar plano' }}</button>
                <a href="{{ route('platform.plans.index') }}" class="btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>

    @push('page-scripts')
    <script>
    (function () {
        var display = document.getElementById('value_display');
        var hidden = document.getElementById('value');

        function formatFromDigits(digits) {
            if (!digits.length) return '';
            var s = digits.padStart(2, '0');
            var cents = s.slice(-2);
            var intPart = s.slice(0, -2);
            if (intPart.length > 0) {
                intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            } else {
                intPart = '0';
            }
            return intPart + ',' + cents;
        }

        function formatBrl(num) {
            var n = parseFloat(num);
            if (isNaN(n) || n < 0) return '';
            var fixed = n.toFixed(2);
            var parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return parts[0] + ',' + parts[1];
        }

        function displayToValue(str) {
            var s = (str + '').replace(/\D/g, '');
            if (s.length === 0) return '';
            return (parseInt(s, 10) / 100).toFixed(2);
        }

        if (hidden && hidden.value && parseFloat(hidden.value) >= 0) {
            display.value = formatBrl(hidden.value);
        }

        display.addEventListener('input', function () {
            var cursor = this.selectionStart;
            var oldLen = this.value.length;
            var digits = this.value.replace(/\D/g, '');
            var formatted = formatFromDigits(digits);
            this.value = formatted || '';
            hidden.value = displayToValue(this.value);
            var newLen = this.value.length;
            this.setSelectionRange(Math.max(0, cursor + (newLen - oldLen)), Math.max(0, cursor + (newLen - oldLen)));
        });

        display.addEventListener('blur', function () {
            if (hidden.value && parseFloat(hidden.value) >= 0) {
                this.value = formatBrl(hidden.value);
            }
        });
    })();
    </script>
    @endpush
@endsection
