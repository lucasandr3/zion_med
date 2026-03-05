@extends('layouts.platform')

@section('title', 'Assinaturas')
@section('subtitle', 'Visão consolidada das assinaturas por empresa.')

@section('content')
    <p class="text-xs text-slate-400 mb-4">
        Exibindo as {{ $subscriptions->count() }} assinaturas mais recentes.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs text-left text-slate-300">
            <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-[0.16em] text-[10px]">
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Plano</th>
                <th class="py-2 px-3">Status</th>
                <th class="py-2 px-3">Próx. vencimento</th>
                <th class="py-2 px-3">Criada em</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
            @forelse($subscriptions as $subscription)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold text-slate-50">
                            {{ $subscription->clinic->name ?? '—' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            ID ASAAS: {{ $subscription->asaas_subscription_id }}
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $subscription->plan_key }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $subscription->status }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ optional($subscription->next_due_date)->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ optional($subscription->created_at)->format('d/m/Y H:i') ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-xs text-slate-500">
                        Nenhuma assinatura encontrada.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

