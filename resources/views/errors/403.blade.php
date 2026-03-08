@extends('layouts.platform')

@section('title', 'Acesso negado')
@section('subtitle', 'Você não tem permissão para acessar esta página.')

@section('content')
    <div class="text-center space-y-4 py-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-red-500/10 text-red-400 border border-red-500/30 mb-2">
            <span class="material-symbols-outlined text-3xl">block</span>
        </div>
        <h2 class="text-lg font-semibold text-slate-50">Permissão insuficiente</h2>
        <p class="text-sm text-slate-400 max-w-md mx-auto">
            @if(!empty($message))
                {{ $message }}
            @else
                Parece que sua conta não possui os acessos necessários para esta funcionalidade.
            @endif
        </p>
        <div class="flex justify-center gap-3 mt-4">
            @php
                $dashboardRoute = auth()->user() && method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin()
                    ? route('platform.dashboard')
                    : route('dashboard');
            @endphp
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : $dashboardRoute }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-slate-100 hover:bg-slate-700">
                Voltar
            </a>
            <a href="{{ $dashboardRoute }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500">
                Ir para o painel
            </a>
        </div>
    </div>
@endsection

