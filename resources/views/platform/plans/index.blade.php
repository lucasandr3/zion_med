@extends('layouts.platform')

@section('title', 'Planos')
@section('subtitle', 'Planos disponíveis para assinatura. Trial padrão: ' . $trialDays . ' dias.')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs" style="color:var(--c-muted)">
            {{ $plans->count() }} {{ $plans->count() === 1 ? 'plano' : 'planos' }}.
        </p>
        <a href="{{ route('platform.plans.create') }}" class="btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px">add</span>
            Novo plano
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($plans as $plan)
            <div class="card flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold" style="color:var(--c-text)">{{ $plan->name }}</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded" style="background:var(--c-soft);color:var(--c-muted)">{{ $plan->key }}</span>
                </div>
                <p class="text-2xl font-bold mb-1" style="color:var(--c-primary)">
                    R$ {{ number_format($plan->value, 2, ',', '.') }}
                    <span class="text-sm font-normal" style="color:var(--c-muted)">/mês</span>
                </p>
                @if($plan->description)
                    <p class="text-xs mt-2 leading-relaxed flex-1" style="color:var(--c-muted)">{{ $plan->description }}</p>
                @endif
                <div class="flex items-center gap-2 mt-3 pt-3" style="border-top:1px solid var(--c-border)">
                    @if(!$plan->is_active)
                        <span class="text-xs px-2 py-0.5 rounded" style="background:var(--c-soft);color:var(--c-muted)">Inativo</span>
                    @endif
                    <a href="{{ route('platform.plans.edit', $plan) }}" class="btn-cell text-xs">
                        <span class="material-symbols-outlined" style="font-size:14px">edit</span>
                        Editar
                    </a>
                    <form action="{{ route('platform.plans.destroy', $plan) }}" method="post" class="inline" onsubmit="return confirm('Remover este plano?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs px-2 py-1 rounded transition-colors" style="color:var(--c-muted);border:1px solid var(--c-border)" onmouseover="this.style.color='#ef4444';this.style.borderColor='#ef4444'" onmouseout="this.style.color='var(--c-muted)';this.style.borderColor='var(--c-border)'">
                            Excluir
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @if($plans->isEmpty())
        <div class="card text-center py-8" style="color:var(--c-muted)">
            <p class="text-sm mb-3">Nenhum plano cadastrado.</p>
            <a href="{{ route('platform.plans.create') }}" class="btn-primary">Criar primeiro plano</a>
        </div>
    @endif
@endsection
