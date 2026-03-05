@extends('layouts.platform')

@section('title', 'Faturas / cobranças')
@section('subtitle', 'Visão consolidada das faturas dos clientes.')

@section('content')
    <p class="text-xs text-slate-400 mb-4">
        Exibindo as {{ $payments->count() }} faturas mais recentes por data de vencimento.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs text-left text-slate-300">
            <thead class="border-b border-slate-800 text-slate-400 uppercase tracking-[0.16em] text-[10px]">
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Valor</th>
                <th class="py-2 px-3">Vencimento</th>
                <th class="py-2 px-3">Situação</th>
                <th class="py-2 px-3">Pago em</th>
                <th class="py-2 pl-3 text-right">Link boleto / NF</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
            @forelse($payments as $payment)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold text-slate-50">
                            {{ $payment->clinic->name ?? '—' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            ID ASAAS: {{ $payment->asaas_payment_id }}
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        @if(!is_null($payment->value))
                            R$ {{ number_format($payment->value, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ optional($payment->due_date)->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ $payment->status }}
                    </td>
                    <td class="py-2.5 px-3 text-[11px]">
                        {{ optional($payment->paid_at)->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right text-[11px]">
                        @if($payment->bank_slip_url)
                            <a href="{{ $payment->bank_slip_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-700/80 px-2.5 py-1.5 text-[11px] font-medium text-slate-100 hover:border-indigo-500/80 hover:text-indigo-200">
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                Abrir
                            </a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-xs text-slate-500">
                        Nenhuma fatura encontrada.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

