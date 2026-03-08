@extends('layouts.platform')

@section('title', 'Meus logs de auditoria')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">history</span>
            </div>
            <div>
                <h1>Meus logs de auditoria</h1>
                <p style="font-size:0.875rem;color:var(--c-muted);margin-top:2px">Ações realizadas por você na plataforma</p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Ação</th>
                    <th>Empresa</th>
                    <th>Detalhe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;color:var(--c-muted);font-size:0.8125rem">
                        {{ $log->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.8rem;font-weight:600;color:var(--c-text)">
                            <span class="material-symbols-outlined" style="font-size:16px;color:var(--c-primary)">info</span>
                            {{ \App\Helpers\AuditLogLabel::actionLabel($log->action) }}
                        </span>
                    </td>
                    <td style="color:var(--c-text)">
                        {{ $log->organization?->name ?? '—' }}
                    </td>
                    <td style="font-size:0.8125rem;color:var(--c-muted)">
                        @if($log->entity_type)
                            {{ \App\Helpers\AuditLogLabel::entityTypeLabel($log->entity_type) }} #{{ $log->entity_id }}
                        @endif
                        @if(!empty($log->meta_json))
                            @foreach($log->meta_json as $k => $v)
                                @if(is_scalar($v))
                                    <span style="display:inline-block;margin-right:6px">{{ \App\Helpers\AuditLogLabel::metaKeyLabel($k) }}: {{ \App\Helpers\AuditLogLabel::metaValueLabel($k, $v) }}</span>
                                @endif
                            @endforeach
                        @endif
                        @if(!$log->entity_type && empty($log->meta_json))
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:3rem 1rem">
                        <span class="material-symbols-outlined" style="font-size:36px;color:var(--c-border);display:block;margin-bottom:8px">history</span>
                        <span style="font-size:0.875rem;color:var(--c-muted)">Nenhum registro de auditoria.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid var(--c-border)">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
