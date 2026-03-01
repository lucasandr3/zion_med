@extends('layouts.app')

@section('title', 'Protocolo ' . ($protocolo->protocol_number ?? $protocolo->id))
@section('header_back_url', route('protocolos.index'))
@section('header_back_label', 'Voltar para Protocolos')

@section('content')
    {{-- Header da página --}}
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">article</span>
            </div>
            <div>
                <h1>Protocolo {{ $protocolo->protocol_number ?? $protocolo->id }}</h1>
                <p class="page-header-subtitle">
                    {{ $protocolo->template->name }} ·
                    @if($protocolo->status->value === 'pending')
                        <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">schedule</span>
                            {{ $protocolo->status->label() }}
                        </span>
                    @elseif($protocolo->status->value === 'approved')
                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">check_circle</span>
                            {{ $protocolo->status->label() }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">cancel</span>
                            {{ $protocolo->status->label() }}
                        </span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
                <a href="{{ route('protocolos.pdf', $protocolo) }}" target="_blank"
                   class="inline-flex items-center gap-2 bg-primary text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition-all text-sm shadow-sm">
                    <span class="material-symbols-outlined" style="font-size:18px">picture_as_pdf</span>
                    Baixar PDF
                </a>
                @can('approve-submission', $protocolo)
                @if($protocolo->status->value === 'pending')
                    <button type="button" id="btn-aprovar-reprovar"
                            class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-amber-700 transition-colors text-sm cursor-pointer">
                        <span class="material-symbols-outlined" style="font-size:18px">rate_review</span>
                        Aprovar / Reprovar
                    </button>
                @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- Dados do protocolo (resumo) --}}
    <div class="card mb-6" style="padding:1.25rem 1.5rem">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">info</span>
            <h2 class="text-sm font-semibold" style="color:var(--c-text)">Dados do protocolo</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--c-muted)">Data de submissão</p>
                <p style="color:var(--c-text)">{{ $protocolo->submitted_at?->format('d/m/Y H:i') ?? $protocolo->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--c-muted)">Submetente</p>
                <p style="color:var(--c-text)">{{ $protocolo->submitter_name ?? $protocolo->submittedByUser?->name ?? '—' }}</p>
                @if($protocolo->submitter_email || $protocolo->submittedByUser?->email)
                    <p class="text-xs text-muted mt-0.5">{{ $protocolo->submitter_email ?? $protocolo->submittedByUser?->email }}</p>
                @endif
            </div>
            @if($protocolo->approved_at || $protocolo->approvedByUser)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--c-muted)">Revisado em</p>
                <p style="color:var(--c-text)">{{ $protocolo->approved_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--c-muted)">Revisado por</p>
                <p style="color:var(--c-text)">{{ $protocolo->approvedByUser?->name ?? '—' }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Histórico e comentários --}}
    @php
        $timelineEvents = $protocolo->events->isEmpty()
            ? collect([(object)['type' => 'created', 'type_label' => 'Protocolo criado', 'body' => null, 'user' => null, 'created_at' => $protocolo->created_at]])
            : $protocolo->events;
    @endphp
    <div class="card mb-6" style="padding:1.25rem 1.5rem">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">history</span>
            <h2 class="text-sm font-semibold" style="color:var(--c-text)">Histórico por data</h2>
        </div>
        <div class="space-y-4">
            @foreach($timelineEvents as $event)
            <div class="flex gap-3">
                @php $evType = is_object($event) ? $event->type : 'comment'; @endphp
                <div class="shrink-0 w-2 h-2 rounded-full mt-2 {{ $evType === 'approved' ? 'bg-green-500' : ($evType === 'rejected' ? 'bg-red-500' : ($evType === 'created' ? 'bg-primary' : 'bg-muted')) }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium" style="color:var(--c-text)">{{ $event->type_label ?? 'Comentário' }}</p>
                    @if(!empty($event->body))
                        <p class="text-sm text-muted mt-0.5">{{ $event->body }}</p>
                    @endif
                    <p class="text-xs text-muted mt-1">
                        @if(isset($event->user) && $event->user)
                            {{ $event->user->name }} ·
                        @endif
                        {{ isset($event->created_at) ? $event->created_at->format('d/m/Y H:i') : '' }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Adicionar comentário --}}
        <form action="{{ route('protocolos.comentario', $protocolo) }}" method="POST" class="mt-5 pt-5 border-t border-border-soft">
            @csrf
            <label class="form-label">Novo comentário interno</label>
            <textarea name="body" rows="2" required maxlength="2000" class="form-input max-w-xl" placeholder="Adicione um comentário..."></textarea>
            @error('body')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            <button type="submit" class="mt-2 inline-flex items-center gap-2 btn-primary text-sm">
                <span class="material-symbols-outlined" style="font-size:16px">add_comment</span>
                Adicionar comentário
            </button>
        </form>
    </div>

    {{-- Review form --}}
    @if($protocolo->status->value === 'pending' && auth()->user()?->can('approve-submission', $protocolo))
    <div id="revisao_form" class="hidden mb-6 bg-surface rounded-xl border border-border-soft shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-amber-500" style="font-size:20px">rate_review</span>
            <h2 class="font-semibold text-content">Revisar protocolo</h2>
        </div>
        <form id="form-revisao" action="{{ route('protocolos.revisao', $protocolo) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Situação</label>
                <select name="status" required class="form-select max-w-xs" id="revisao_status">
                    <option value="approved">Aprovado</option>
                    <option value="rejected">Reprovado</option>
                </select>
            </div>
            <div>
                <label class="form-label">Comentário (opcional)</label>
                <textarea name="review_comment" rows="2" class="form-input max-w-xl"></textarea>
            </div>
            <button type="submit" id="btn-revisao-submit"
                    class="inline-flex items-center gap-2 bg-primary text-white font-semibold px-5 py-2 rounded-lg hover:opacity-90 active:scale-95 transition-all text-sm shadow-sm">
                <span class="material-symbols-outlined" style="font-size:18px">send</span>
                Enviar revisão
            </button>
        </form>
    </div>
    <script>
    (function(){
        var btn = document.getElementById('btn-aprovar-reprovar');
        var form = document.getElementById('revisao_form');
        if (btn && form) {
            btn.addEventListener('click', function(){
                form.classList.toggle('hidden');
                if (!form.classList.contains('hidden')) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
        var formRevisao = document.getElementById('form-revisao');
        if (formRevisao) {
            formRevisao.addEventListener('submit', function(e){
                var status = document.getElementById('revisao_status');
                if (status && status.value === 'rejected' && !confirm('Tem certeza que deseja reprovar este protocolo? O submetente pode ser notificado.')) {
                    e.preventDefault();
                }
            });
        }
    })();
    </script>
    @endif

    {{-- Review comment --}}
    @if($protocolo->review_comment)
    <div class="mb-6 bg-surface rounded-xl border border-border-soft p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-muted" style="font-size:18px">comment</span>
            <p class="text-sm font-semibold text-content">Comentário da revisão</p>
        </div>
        <p class="text-sm text-muted ml-[26px]">{{ $protocolo->review_comment }}</p>
        @if($protocolo->approvedByUser)
            <p class="text-xs text-muted mt-2 ml-[26px]">
                Por {{ $protocolo->approvedByUser->name }} em {{ $protocolo->approved_at?->format('d/m/Y H:i') }}
            </p>
        @endif
    </div>
    @endif

    {{-- Data table --}}
    <div class="table-card mb-6">
        <table>
            <thead>
                <tr>
                    <th class="w-1/3">Campo</th>
                    <th>Resposta</th>
                </tr>
            </thead>
            <tbody>
                @php $valuesKeyed = $protocolo->getValuesKeyed(); @endphp
                @foreach($protocolo->template->fields as $field)
                @if($field->type !== 'file' && $field->type !== 'signature')
                @php $val = $valuesKeyed->get($field->name_key); @endphp
                <tr>
                    <td class="font-medium text-muted">{{ $field->label }}</td>
                    <td>{{ $val ? ($val->value_json ? (is_array($val->value_json) ? implode(', ', $val->value_json) : $val->value_json) : $val->value_text) : '—' }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Signatures --}}
    @if($protocolo->signatures->isNotEmpty())
    <div class="bg-surface rounded-xl border border-border-soft p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">draw</span>
            <h2 class="font-semibold text-content">Assinatura(s)</h2>
        </div>
        <div class="flex flex-wrap gap-4">
            @foreach($protocolo->signatures as $sig)
                <img src="{{ asset('storage/'.$sig->image_path) }}" alt="Assinatura"
                     class="max-w-xs border border-border-soft rounded-lg bg-white p-2">
            @endforeach
        </div>
    </div>
    @endif

    {{-- Attachments --}}
    @if($protocolo->attachments->isNotEmpty())
    <div class="bg-surface rounded-xl border border-border-soft p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">attach_file</span>
            <h2 class="font-semibold text-content">Anexos</h2>
        </div>
        <div class="space-y-2">
            @foreach($protocolo->attachments as $att)
                <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg border border-border-soft hover:bg-content/[0.02] transition-colors group">
                    <span class="material-symbols-outlined text-muted group-hover:text-primary transition-colors" style="font-size:20px">description</span>
                    <div>
                        <p class="text-sm font-medium text-content group-hover:text-primary transition-colors">{{ $att->original_name }}</p>
                        <p class="text-xs text-muted">{{ number_format($att->size / 1024, 1) }} KB</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif
@endsection
