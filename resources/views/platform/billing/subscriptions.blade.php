@extends('layouts.platform')

@section('title', 'Assinaturas')
@section('subtitle', 'Visão consolidada das assinaturas por empresa.')

@section('content')
    <p class="text-xs mb-4" style="color:var(--c-muted)">
        Exibindo as {{ $subscriptions->count() }} assinaturas mais recentes.
    </p>

    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--c-border);background:var(--c-surface)">
        <table class="platform-table">
            <thead>
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Plano</th>
                <th class="py-2 px-3">Status</th>
                <th class="py-2 px-3">Próx. vencimento</th>
                <th class="py-2 px-3">Criada em</th>
            </tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $subscription)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold" style="color:var(--c-text)">
                            {{ $subscription->clinic->name ?? '—' }}
                        </div>
                        <div class="text-[11px] cell-muted">
                            ID ASAAS: {{ $subscription->asaas_subscription_id }}
                        </div>
                    </td>
                    <td class="py-2.5 px-3">{{ $subscription->plan_key }}</td>
                    <td class="py-2.5 px-3">{{ $subscription->status }}</td>
                    <td class="py-2.5 px-3">{{ optional($subscription->next_due_date)->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-2.5 px-3">{{ optional($subscription->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-xs cell-muted">
                        Nenhuma assinatura encontrada.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
