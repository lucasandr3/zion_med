@extends('layouts.platform')

@section('title', 'Faturas / cobranças')
@section('subtitle', 'Visão consolidada das faturas dos clientes.')

@section('content')
    <p class="text-xs mb-4" style="color:var(--c-muted)">
        Exibindo as {{ $payments->count() }} faturas mais recentes por data de vencimento.
    </p>

    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--c-border);background:var(--c-surface)">
        <table class="platform-table">
            <thead>
            <tr>
                <th class="py-2 pr-3">Empresa</th>
                <th class="py-2 px-3">Valor</th>
                <th class="py-2 px-3">Vencimento</th>
                <th class="py-2 px-3">Situação</th>
                <th class="py-2 px-3">Pago em</th>
                <th class="py-2 pl-3 text-right">Link boleto / NF</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="text-xs font-semibold" style="color:var(--c-text)">
                            {{ $payment->clinic->name ?? '—' }}
                        </div>
                        <div class="text-[11px] cell-muted">
                            ID ASAAS: {{ $payment->asaas_payment_id }}
                        </div>
                    </td>
                    <td class="py-2.5 px-3">
                        @if(!is_null($payment->value))
                            R$ {{ number_format($payment->value, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="py-2.5 px-3">{{ optional($payment->due_date)->format('d/m/Y') ?? '—' }}</td>
                    <td class="py-2.5 px-3">{{ $payment->status }}</td>
                    <td class="py-2.5 px-3">{{ optional($payment->paid_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="py-2.5 pl-3 text-right">
                        @if($payment->bank_slip_url)
                            <a href="{{ $payment->bank_slip_url }}" target="_blank" rel="noopener noreferrer" class="btn-cell">
                                <span class="material-symbols-outlined" style="font-size:14px">open_in_new</span>
                                Abrir
                            </a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-xs cell-muted">
                        Nenhuma fatura encontrada.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
