<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;

class TemplateVersionService
{
    /**
     * Cria uma nova versão do template com snapshot atual dos campos (para evidência e auditoria).
     */
    public function createVersionForTemplate(FormTemplate $template): FormTemplateVersion
    {
        $template->load('fields');
        $nextVersion = FormTemplateVersion::where('form_template_id', $template->id)->max('version') + 1;
        $fieldsSnapshot = $template->fields->map(fn ($f) => [
            'id' => $f->id,
            'type' => $f->type,
            'label' => $f->label,
            'name_key' => $f->name_key,
            'required' => $f->required,
            'options_json' => $f->options_json,
            'sort_order' => $f->sort_order,
        ])->values()->all();

        return FormTemplateVersion::create([
            'form_template_id' => $template->id,
            'version' => $nextVersion,
            'name' => $template->name,
            'description' => $template->description,
            'fields_snapshot' => $fieldsSnapshot,
        ]);
    }

    /**
     * Retorna a versão atual do template (última criada) ou cria uma nova se não existir.
     */
    public function getOrCreateCurrentVersion(FormTemplate $template): FormTemplateVersion
    {
        $version = FormTemplateVersion::where('form_template_id', $template->id)->orderByDesc('version')->first();
        if ($version) {
            return $version;
        }
        return $this->createVersionForTemplate($template);
    }
}
