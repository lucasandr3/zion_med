@foreach($protocolos as $s)
<tr class="protocolo-row"
    data-href="{{ route('protocolos.show', $s) }}"
    style="cursor:pointer;transition:background 0.1s">
    <td>
        <div style="font-size:0.7rem;font-weight:600;color:var(--c-primary);font-family:monospace;white-space:nowrap">
            {{ $s->protocol_number ?? $s->id }}
        </div>
        <div style="font-size:0.7rem;color:var(--c-muted);margin-top:1px">
            {{ $s->submitted_at?->format('d/m/Y H:i') ?? $s->created_at->format('d/m/Y H:i') }}
        </div>
    </td>
    <td style="max-width:180px">
        <div style="font-size:0.8125rem;color:var(--c-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $s->template->name }}
        </div>
    </td>
    <td>
        @if($s->status->value === 'pending')
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(251,191,36,0.12);color:#f59e0b;white-space:nowrap">
                <span class="material-symbols-outlined" style="font-size:11px">schedule</span>
                {{ $s->status->label() }}
            </span>
        @elseif($s->status->value === 'approved')
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(34,197,94,0.1);color:#22c55e;white-space:nowrap">
                <span class="material-symbols-outlined" style="font-size:11px;font-variation-settings:'FILL' 1">check_circle</span>
                {{ $s->status->label() }}
            </span>
        @else
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:3px 8px;border-radius:9999px;background:rgba(239,68,68,0.1);color:#f87171;white-space:nowrap">
                <span class="material-symbols-outlined" style="font-size:11px;font-variation-settings:'FILL' 1">cancel</span>
                {{ $s->status->label() }}
            </span>
        @endif
    </td>
    <td>
        <div style="font-size:0.8125rem;color:var(--c-text)">{{ $s->submitter_name ?? $s->submittedByUser?->name ?? '—' }}</div>
        <div style="font-size:0.7rem;color:var(--c-muted)">{{ $s->submitter_email ?? $s->submittedByUser?->email ?? '' }}</div>
    </td>
    <td style="color:var(--c-muted);font-size:0.75rem;white-space:nowrap">
        {{ $s->approved_at?->format('d/m/Y H:i') ?? '—' }}
    </td>
    <td style="color:var(--c-muted);font-size:0.75rem">
        {{ $s->approvedByUser?->name ?? '—' }}
    </td>
</tr>
@endforeach
