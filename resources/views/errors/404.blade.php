@extends('layouts.platform')

@section('title', 'Página não encontrada')
@section('subtitle', 'Não conseguimos localizar o recurso solicitado.')

@section('content')
    <div class="text-center space-y-4 py-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-500/10 text-slate-300 border border-slate-500/30 mb-2">
            <span class="material-symbols-outlined text-3xl">travel_explore</span>
        </div>
        <h2 class="text-lg font-semibold text-slate-50">Essa página não existe ou foi movida</h2>
        <p class="text-sm text-slate-400 max-w-md mx-auto">
            @if(!empty($message))
                {{ $message }}
            @else
                Verifique se o endereço foi digitado corretamente ou utilize o menu para navegar até uma área disponível.
            @endif
        </p>
        <div class="flex justify-center gap-3 mt-4">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-slate-100 hover:bg-slate-700">
                Voltar
            </a>
            <a href="{{ route('dashboard') }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500">
                Ir para o painel
            </a>
        </div>
    </div>
@endsection

