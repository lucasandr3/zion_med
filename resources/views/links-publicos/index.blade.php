@extends('layouts.app')

@section('title', 'Links para enviar')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">link</span>
            </div>
            <div>
                <h1>Links para enviar</h1>
                <p class="page-header-subtitle">Copie o link do formulário e envie pelo WhatsApp ou outro canal. Apenas templates com link público ativo aparecem aqui.</p>
            </div>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        @forelse($templates as $t)
            @php $publicFullUrl = url()->route('formulario-publico.show', $t->public_token); @endphp
            <div class="flex flex-wrap items-center justify-between gap-4 py-4 px-5"
                 style="{{ $loop->last ? '' : 'border-bottom:1px solid var(--c-border)' }}">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="material-symbols-outlined shrink-0" style="font-size:20px;color:var(--c-muted)">description</span>
                    <div class="min-w-0">
                        <p class="font-medium" style="color:var(--c-text)">{{ $t->name }}</p>
                        @if($t->category_label)
                            <p class="text-xs mt-0.5" style="color:var(--c-muted)">{{ $t->category_label }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                            data-copy-url="{{ $publicFullUrl }}"
                            onclick="copyPublicLinkFromBtn(this)"
                            class="inline-flex items-center gap-2 bg-green-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <span class="material-symbols-outlined" style="font-size:18px">content_copy</span>
                        <span class="copy-label">Copiar link</span>
                    </button>
                    <a href="{{ $publicFullUrl }}"
                       target="_blank"
                       rel="noopener"
                       class="inline-flex items-center gap-2 bg-content/5 text-muted font-medium px-4 py-2 rounded-lg hover:bg-content/10 hover:text-content transition-colors text-sm">
                        <span class="material-symbols-outlined" style="font-size:18px">open_in_new</span>
                        Abrir
                    </a>
                    @can('manage-templates')
                    <a href="{{ route('templates.campos.index', $t) }}"
                       class="inline-flex items-center gap-2 bg-content/5 text-muted font-medium px-4 py-2 rounded-lg hover:bg-content/10 hover:text-content transition-colors text-sm"
                       data-tooltip="Campos do template" aria-label="Campos do template">
                        <span class="material-symbols-outlined" style="font-size:18px">tune</span>
                    </a>
                    @endcan
                </div>
            </div>
        @empty
            <div class="px-5 py-16 text-center">
                <span class="material-symbols-outlined block mb-3" style="font-size:48px;color:var(--c-border)">link_off</span>
                <p class="font-medium" style="color:var(--c-text)">Nenhum link público ativo</p>
                <p class="text-sm mt-1" style="color:var(--c-muted)">
                    Gere um link público em <strong>Templates</strong> → escolha um template → <strong>Campos</strong> → <strong>Gerar link público</strong>.
                </p>
                @can('manage-templates')
                <a href="{{ route('templates.index') }}" class="inline-flex items-center gap-2 mt-4 btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">description</span>
                    Ir para Templates
                </a>
                @endcan
            </div>
        @endforelse
    </div>

    <script>
    function copyPublicLink(url, btn) {
        navigator.clipboard.writeText(url).then(function() {
            var lbl = btn && btn.querySelector && btn.querySelector('.copy-label');
            if (lbl) { var t = lbl.textContent; lbl.textContent = 'Copiado!'; setTimeout(function(){ lbl.textContent = t; }, 2000); }
        });
    }
    function copyPublicLinkFromBtn(btn) { copyPublicLink(btn.getAttribute('data-copy-url') || '', btn); }
    </script>
@endsection
