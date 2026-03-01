@extends('layouts.app')

@section('title', 'Protocolos')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">inbox</span>
            </div>
            <div>
                <h1>Protocolos</h1>
            </div>
        </div>
        <a href="{{ route('protocolos.exportar') }}?{{ http_build_query(request()->query()) }}"
           class="btn-ghost btn-default-bg">
            <span class="material-symbols-outlined" style="font-size:16px">download</span>
            Exportar CSV
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card mb-5" style="padding:1rem 1.25rem">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end">
            <div>
                <label class="form-label">Template</label>
                <select name="template_id" class="form-select" style="min-width:160px">
                    <option value="">Todos</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}" {{ request('template_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Situação</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="pending"   {{ request('status')==='pending'   ? 'selected':'' }}>Pendente</option>
                    <option value="approved"  {{ request('status')==='approved'  ? 'selected':'' }}>Aprovada</option>
                    <option value="rejected"  {{ request('status')==='rejected'  ? 'selected':'' }}>Reprovada</option>
                </select>
            </div>
            <div>
                <label class="form-label">Data início</label>
                <input type="text" name="data_inicio" value="{{ request('data_inicio') }}" class="form-input flatpickr-date" placeholder="dd/mm/aaaa" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Data fim</label>
                <input type="text" name="data_fim" value="{{ request('data_fim') }}" class="form-input flatpickr-date" placeholder="dd/mm/aaaa" autocomplete="off">
            </div>
            <button type="submit" class="btn-primary">
                <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
                Filtrar
            </button>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Protocolo</th>
                    <th>Template</th>
                    <th>Situação</th>
                    <th>Submetente</th>
                    <th>Data</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissoes as $s)
                <tr>
                    <td><code style="font-size:0.75rem;background:var(--c-soft);padding:2px 6px;border-radius:4px;color:var(--c-muted)">{{ $s->protocol_number ?? $s->id }}</code></td>
                    <td>{{ $s->template->name }}</td>
                    <td>
                        @if($s->status->value === 'pending')
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(251,191,36,0.12);color:#f59e0b">
                                <span class="material-symbols-outlined" style="font-size:12px">schedule</span>
                                {{ $s->status->label() }}
                            </span>
                        @elseif($s->status->value === 'approved')
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(34,197,94,0.1);color:#22c55e">
                                <span class="material-symbols-outlined" style="font-size:12px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">check_circle</span>
                                {{ $s->status->label() }}
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(239,68,68,0.1);color:#f87171">
                                <span class="material-symbols-outlined" style="font-size:12px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">cancel</span>
                                {{ $s->status->label() }}
                            </span>
                        @endif
                    </td>
                    <td style="color:var(--c-muted)">{{ $s->submitter_name ?? $s->submittedByUser?->name ?? '—' }}</td>
                    <td style="color:var(--c-muted);font-size:0.75rem">{{ $s->submitted_at?->format('d/m/Y H:i') ?? $s->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:2px">
                            <a href="{{ route('protocolos.show', $s) }}" data-tooltip="Ver" aria-label="Ver protocolo" class="action-btn">
                                <span class="material-symbols-outlined" style="font-size:18px">visibility</span>
                            </a>
                            <a href="{{ route('protocolos.pdf', $s) }}" target="_blank" data-tooltip="Baixar PDF" aria-label="Baixar PDF" class="action-btn">
                                <span class="material-symbols-outlined" style="font-size:18px">picture_as_pdf</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:3rem 1rem">
                        <span class="material-symbols-outlined" style="font-size:36px;color:var(--c-border);display:block;margin-bottom:8px">inbox</span>
                        <span style="font-size:0.875rem;color:var(--c-muted)">Nenhum protocolo encontrado.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem">{{ $submissoes->links() }}</div>
@endsection
