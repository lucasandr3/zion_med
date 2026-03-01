@extends('layouts.app')

@section('title', 'Editar template')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <span class="material-symbols-outlined">edit_note</span>
            </div>
            <div>
                <h1>Editar template</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('templates.update', $template) }}" method="POST" style="display:flex;flex-direction:column;gap:1.25rem">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="form-input">
                @error('name')<p style="color:#f87171;font-size:0.75rem;margin-top:4px">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="3" class="form-input">{{ old('description', $template->description) }}</textarea>
            </div>
            <div style="display:flex;align-items:center;gap:1.5rem">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="form-checkbox">
                    <span class="form-label" style="margin:0">Ativo</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="hidden" name="public_enabled" value="0">
                    <input type="checkbox" name="public_enabled" value="1" {{ old('public_enabled', $template->public_enabled) ? 'checked' : '' }} class="form-checkbox">
                    <span class="form-label" style="margin:0">Formulário público</span>
                </label>
            </div>
            <div style="display:flex;align-items:center;gap:12px;padding-top:0.5rem">
                <button type="submit" class="btn-primary">
                    <span class="material-symbols-outlined" style="font-size:16px">save</span>
                    Salvar alterações
                </button>
                <a href="{{ route('templates.index') }}" style="font-size:0.8125rem;color:var(--c-muted);text-decoration:none">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
