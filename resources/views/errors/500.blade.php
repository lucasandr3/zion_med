@extends('layouts.platform')

@section('title', 'Ocorreu um erro inesperado')
@section('subtitle', 'Nossa equipe já foi notificada (ou será) para analisar o ocorrido.')

@section('content')
    <div class="text-center space-y-4 py-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-300 border border-amber-500/30 mb-2">
            <span class="material-symbols-outlined text-3xl">error</span>
        </div>
        <h2 class="text-lg font-semibold text-slate-50">Algo deu errado ao processar sua solicitação</h2>
        <p class="text-sm text-slate-400 max-w-md mx-auto">
            @if(!empty($message))
                {{ $message }}
            @else
                Tente novamente em alguns instantes. Se o problema persistir, entre em contato com o suporte informando o horário e a ação realizada.
            @endif
        </p>
        <div class="flex justify-center gap-3 mt-4">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-slate-100 hover:bg-slate-700">
                Tentar novamente
            </a>
            <a href="{{ route('dashboard') }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500">
                Ir para o painel
            </a>
        </div>
    </div>
@endsection

