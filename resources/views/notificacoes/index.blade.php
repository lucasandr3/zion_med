@extends($layout ?? 'layouts.app')

@section('title', 'Notificações')
@section('subtitle', '')

@section('content')
<div style="margin:0 auto">

    {{-- Cabeçalho --}}
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">notifications</span>
            </div>
            <div>
                <h1>Notificações</h1>
            </div>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
            {{-- Filtro --}}
            <div style="display:flex;border:1px solid var(--c-border);border-radius:8px;overflow:hidden">
                <a href="{{ route('notificacoes.index', ['filtro' => 'todas']) }}"
                   style="padding:0.375rem 0.875rem;font-size:0.8125rem;font-weight:500;text-decoration:none;transition:all 0.15s;
                          {{ $filter === 'todas' ? 'background:var(--c-primary);color:#fff' : 'background:transparent;color:var(--c-muted)' }}">
                    Todas
                </a>
                <a href="{{ route('notificacoes.index', ['filtro' => 'nao_lidas']) }}"
                   style="padding:0.375rem 0.875rem;font-size:0.8125rem;font-weight:500;text-decoration:none;transition:all 0.15s;border-left:1px solid var(--c-border);
                          {{ $filter === 'nao_lidas' ? 'background:var(--c-primary);color:#fff' : 'background:transparent;color:var(--c-muted)' }}">
                    Não lidas
                    @if($unreadCount > 0)
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:9px;font-size:0.625rem;font-weight:700;padding:0 4px;margin-left:4px;
                              {{ $filter === 'nao_lidas' ? 'background:rgba(255,255,255,0.25);color:#fff' : 'background:var(--c-primary);color:#fff' }}">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>
            </div>

            {{-- Marcar todas como lidas --}}
            @if($unreadCount > 0)
            <form method="POST" action="{{ route('notificacoes.read.all') }}">
                @csrf
                <button type="submit"
                        style="display:flex;align-items:center;gap:6px;padding:0.375rem 0.875rem;border-radius:8px;border:1px solid var(--c-border);background:transparent;color:var(--c-muted);font-size:0.8125rem;font-weight:500;cursor:pointer;font-family:inherit;transition:all 0.15s"
                        onmouseover="this.style.background='var(--c-soft)';this.style.color='var(--c-text)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:15px">done_all</span>
                    Marcar todas como lidas
                </button>
            </form>
            @endif

            {{-- Limpar todas --}}
            @if($notifications->total() > 0)
            <form method="POST" action="{{ route('notificacoes.destroy.all') }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Remover todas as notificações?')"
                        style="display:flex;align-items:center;gap:6px;padding:0.375rem 0.875rem;border-radius:8px;border:1px solid var(--c-border);background:transparent;color:var(--c-muted);font-size:0.8125rem;font-weight:500;cursor:pointer;font-family:inherit;transition:all 0.15s"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.borderColor='rgba(239,68,68,0.3)';this.style.color='#ef4444'"
                        onmouseout="this.style.background='transparent';this.style.borderColor='var(--c-border)';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:15px">delete_sweep</span>
                    Limpar tudo
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Lista --}}
    @forelse($notifications as $notification)
        @php
            $data    = $notification->data;
            $isRead  = $notification->read_at !== null;
            $type    = $data['type'] ?? 'info';
            $icon    = $data['icon'] ?? 'notifications';
            $url     = $data['url'] ?? null;

            $iconColor = match($type) {
                'novo_protocolo'   => 'var(--c-primary)',
                'protocolo_aprovado' => '#10b981',
                'protocolo_reprovado' => '#ef4444',
                'novo_comentario'  => '#f59e0b',
                default            => 'var(--c-muted)',
            };
        @endphp

        <div style="display:flex;gap:1rem;padding:1rem 1.25rem;border-radius:12px;border:1px solid var(--c-border);background:var(--c-surface);margin-bottom:0.625rem;transition:box-shadow 0.15s;position:relative;
                    {{ !$isRead ? 'border-left:3px solid var(--c-primary)' : '' }}"
             onmouseover="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.07)'"
             onmouseout="this.style.boxShadow='none'">

            {{-- Ponto não lida --}}
            @if(!$isRead)
                <span style="position:absolute;top:12px;right:12px;width:8px;height:8px;border-radius:50%;background:var(--c-primary)"></span>
            @endif

            {{-- Ícone --}}
            <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, {{ $iconColor }} 12%, var(--c-soft))">
                <span class="material-symbols-outlined" style="font-size:20px;color:{{ $iconColor }}">{{ $icon }}</span>
            </div>

            {{-- Conteúdo --}}
            <div style="flex:1;min-width:0">
                <p style="font-size:0.875rem;font-weight:{{ $isRead ? '400' : '600' }};color:var(--c-text);margin-bottom:0.25rem;line-height:1.4">
                    {{ $data['title'] ?? 'Notificação' }}
                </p>
                <p style="font-size:0.8125rem;color:var(--c-muted);line-height:1.5;margin-bottom:0.5rem">
                    {{ $data['body'] ?? '' }}
                </p>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <span style="font-size:0.75rem;color:var(--c-muted)">
                        {{ $notification->created_at->diffForHumans() }}
                    </span>
                    @if($url)
                        <a href="{{ route('notificacoes.read', $notification->id) }}"
                           style="font-size:0.75rem;font-weight:600;color:var(--c-primary);text-decoration:none"
                           onmouseover="this.style.textDecoration='underline'"
                           onmouseout="this.style.textDecoration='none'">
                            Ver protocolo →
                        </a>
                    @endif
                    @if(!$isRead)
                        <form method="POST" action="{{ route('notificacoes.read', $notification->id) }}" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    style="font-size:0.75rem;font-weight:500;color:var(--c-muted);background:none;border:none;cursor:pointer;font-family:inherit;padding:0;transition:color 0.15s"
                                    onmouseover="this.style.color='var(--c-text)'"
                                    onmouseout="this.style.color='var(--c-muted)'">
                                Marcar como lida
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Botão excluir --}}
            <form method="POST" action="{{ route('notificacoes.destroy', $notification->id) }}" style="flex-shrink:0;align-self:center">
                @csrf
                @method('DELETE')
                <button type="submit"
                        style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:none;background:transparent;cursor:pointer;color:var(--c-muted);transition:all 0.15s"
                        data-tooltip="Remover" aria-label="Remover notificação"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#ef4444'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--c-muted)'">
                    <span class="material-symbols-outlined" style="font-size:16px">close</span>
                </button>
            </form>
        </div>

    @empty
        {{-- Estado vazio --}}
        <div style="text-align:center;padding:4rem 2rem;border-radius:16px;border:1px dashed var(--c-border);background:var(--c-surface)">
            <div style="width:56px;height:56px;border-radius:14px;background:var(--c-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                <span class="material-symbols-outlined" style="font-size:28px;color:var(--c-muted)">notifications_off</span>
            </div>
            <p style="font-size:0.9375rem;font-weight:600;color:var(--c-text);margin-bottom:0.375rem">
                Nenhuma notificação
            </p>
            <p style="font-size:0.875rem;color:var(--c-muted)">
                {{ $filter === 'nao_lidas' ? 'Você não tem notificações não lidas.' : 'Você não tem notificações ainda.' }}
            </p>
        </div>
    @endforelse

    {{-- Paginação --}}
    @if($notifications->hasPages())
        <div style="margin-top:1.5rem">
            {{ $notifications->links() }}
        </div>
    @endif

</div>
@endsection
