@extends('layouts.app')

@section('title', 'Protocolo ' . ($submissao->protocol_number ?? $submissao->id))
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
                <h1>Protocolo {{ $submissao->protocol_number ?? $submissao->id }}</h1>
                <p class="page-header-subtitle">
                    {{ $submissao->template->name }} ·
                    @if($submissao->status->value === 'pending')
                        <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">schedule</span>
                            {{ $submissao->status->label() }}
                        </span>
                    @elseif($submissao->status->value === 'approved')
                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">check_circle</span>
                            {{ $submissao->status->label() }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 font-medium">
                            <span class="material-symbols-outlined" style="font-size:14px">cancel</span>
                            {{ $submissao->status->label() }}
                        </span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
                <a href="{{ route('protocolos.pdf', $submissao) }}" target="_blank"
                   class="inline-flex items-center gap-2 bg-primary text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition-all text-sm shadow-sm">
                    <span class="material-symbols-outlined" style="font-size:18px">picture_as_pdf</span>
                    Baixar PDF
                </a>
                @can('approve-submission', $submissao)
                @if($submissao->status->value === 'pending')
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

    {{-- Review form --}}
    @if($submissao->status->value === 'pending' && auth()->user()?->can('approve-submission', $submissao))
    <div id="revisao_form" class="hidden mb-6 bg-surface rounded-xl border border-border-soft shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-amber-500" style="font-size:20px">rate_review</span>
            <h2 class="font-semibold text-content">Revisar protocolo</h2>
        </div>
        <form action="{{ route('protocolos.revisao', $submissao) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Situação</label>
                <select name="status" required class="form-select max-w-xs">
                    <option value="approved">Aprovado</option>
                    <option value="rejected">Reprovado</option>
                </select>
            </div>
            <div>
                <label class="form-label">Comentário (opcional)</label>
                <textarea name="review_comment" rows="2" class="form-input max-w-xl"></textarea>
            </div>
            <button type="submit"
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
    })();
    </script>
    @endif

    {{-- Review comment --}}
    @if($submissao->review_comment)
    <div class="mb-6 bg-surface rounded-xl border border-border-soft p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-muted" style="font-size:18px">comment</span>
            <p class="text-sm font-semibold text-content">Comentário da revisão</p>
        </div>
        <p class="text-sm text-muted ml-[26px]">{{ $submissao->review_comment }}</p>
        @if($submissao->approvedByUser)
            <p class="text-xs text-muted mt-2 ml-[26px]">
                Por {{ $submissao->approvedByUser->name }} em {{ $submissao->approved_at?->format('d/m/Y H:i') }}
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
                @php $valuesKeyed = $submissao->getValuesKeyed(); @endphp
                @foreach($submissao->template->fields as $field)
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
    @if($submissao->signatures->isNotEmpty())
    <div class="bg-surface rounded-xl border border-border-soft p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">draw</span>
            <h2 class="font-semibold text-content">Assinatura(s)</h2>
        </div>
        <div class="flex flex-wrap gap-4">
            @foreach($submissao->signatures as $sig)
                <img src="{{ asset('storage/'.$sig->image_path) }}" alt="Assinatura"
                     class="max-w-xs border border-border-soft rounded-lg bg-white p-2">
            @endforeach
        </div>
    </div>
    @endif

    {{-- Attachments --}}
    @if($submissao->attachments->isNotEmpty())
    <div class="bg-surface rounded-xl border border-border-soft p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-muted" style="font-size:20px">attach_file</span>
            <h2 class="font-semibold text-content">Anexos</h2>
        </div>
        <div class="space-y-2">
            @foreach($submissao->attachments as $att)
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
