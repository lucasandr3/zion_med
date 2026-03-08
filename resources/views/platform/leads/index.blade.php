@extends('layouts.platform')

@section('title', 'Leads')
@section('subtitle', 'Contatos enviados pelo formulário e WhatsApp da landing.')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs" style="color:var(--c-muted)">
            {{ $requests->count() }} {{ $requests->count() === 1 ? 'lead' : 'leads' }}.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--c-border);background:var(--c-surface)">
        <table class="platform-table">
            <thead>
            <tr>
                <th class="py-2 pr-3">Nome</th>
                <th class="py-2 px-3">Clínica</th>
                <th class="py-2 px-3">E-mail</th>
                <th class="py-2 px-3">WhatsApp</th>
                <th class="py-2 px-3 max-w-[200px]">Mensagem</th>
                <th class="py-2 pl-3 text-right">Data</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td class="py-2.5 pr-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-semibold"
                                 style="background:color-mix(in srgb, var(--c-primary) 18%, transparent);color:var(--c-primary)">
                                {{ mb_strtoupper(mb_substr($req->name, 0, 1)) }}
                            </div>
                            <span class="text-xs font-semibold" style="color:var(--c-text)">{{ $req->name }}</span>
                        </div>
                    </td>
                    <td class="py-2.5 px-3 cell-muted">{{ $req->clinic }}</td>
                    <td class="py-2.5 px-3">
                        <a href="mailto:{{ $req->email }}">{{ $req->email }}</a>
                    </td>
                    <td class="py-2.5 px-3">
                        <a href="https://wa.me/55{{ preg_replace('/\D/', '', $req->phone) }}" target="_blank" rel="noopener">{{ $req->phone }}</a>
                    </td>
                    <td class="py-2.5 px-3 cell-muted max-w-[200px] truncate" title="{{ $req->message }}">
                        {{ $req->message ?: '—' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right cell-muted">
                        {{ $req->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-xs cell-muted">
                        Nenhum lead.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
