@extends('layouts.app')

@section('title', 'Novo template (em branco)')
@section('header_back_url', route('templates.create'))
@section('header_back_label', 'Voltar à escolha de modelo')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">add_circle</span>
            </div>
            <div>
                <h1>Novo template (em branco)</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('templates.store') }}" method="POST" style="display:flex;flex-direction:column;gap:1.25rem">
            @csrf
            <div>
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Ex: Ficha de Anamnese">
                @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="3" class="form-input" placeholder="Breve descrição do template...">{{ old('description') }}</textarea>
                @error('description')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div style="display:flex;align-items:center;gap:1.5rem">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="form-checkbox">
                    <span class="form-label" style="margin:0">Ativo</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="hidden" name="public_enabled" value="0">
                    <input type="checkbox" name="public_enabled" value="1" {{ old('public_enabled') ? 'checked' : '' }} class="form-checkbox">
                    <span class="form-label" style="margin:0">Formulário público</span>
                </label>
            </div>
            <div style="display:flex;align-items:center;gap:12px;padding-top:0.5rem">
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">save</span>
                    Criar template
                </button>
                <a href="{{ route('templates.index') }}" style="font-size:0.8125rem;color:var(--c-muted);text-decoration:none">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
