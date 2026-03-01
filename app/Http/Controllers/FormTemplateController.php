<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormFieldRequest;
use App\Http\Requests\FormTemplateRequest;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Services\AuditService;
use App\Services\PublicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FormTemplateController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private PublicLinkService $publicLinkService
    ) {}

    public function index(): View
    {
        $this->authorize('manage-templates');
        $templates = FormTemplate::orderByRaw('COALESCE(category, \'\')')
            ->orderBy('name')
            ->get();
        $templatesByCategory = $templates->groupBy(fn (FormTemplate $t) => $t->category ?? 'personalizado');
        $categoryLabels = array_merge(['personalizado' => 'Personalizado'], FormTemplate::categoryLabels());
        return view('templates.index', [
            'templatesByCategory' => $templatesByCategory,
            'categoryLabels' => $categoryLabels,
            'templatesCount' => $templates->count(),
        ]);
    }

    /** Página "Links para enviar" – templates com link público ativo para copiar rapidamente (ex.: WhatsApp). */
    public function linksPublicos(Request $request): View
    {
        if (! $request->user()->can('manage-templates') && ! $request->user()->can('view-submissions')) {
            abort(403);
        }
        $templates = FormTemplate::whereNotNull('public_token')
            ->where('public_enabled', true)
            ->orderBy('name')
            ->get();
        return view('links-publicos.index', ['templates' => $templates]);
    }

    public function create(): View
    {
        $this->authorize('manage-templates');
        $templatesByCategory = FormTemplate::with('fields')
            ->whereNotNull('category')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
        $categoryLabels = FormTemplate::categoryLabels();
        return view('templates.create', [
            'templatesByCategory' => $templatesByCategory,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function createBlank(): View
    {
        $this->authorize('manage-templates');
        return view('templates.create-blank');
    }

    public function storeFromTemplate(Request $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('manage-templates');
        $this->authorize('update-template', $template);

        $clinicId = $request->user()->clinic_id ?? session('current_clinic_id');
        $newTemplate = FormTemplate::create([
            'clinic_id' => $clinicId,
            'name' => $template->name,
            'description' => $template->description,
            'category' => null,
            'is_active' => true,
            'public_enabled' => false,
            'created_by' => $request->user()->id,
        ]);

        foreach ($template->fields as $field) {
            FormField::create([
                'template_id' => $newTemplate->id,
                'type' => $field->type,
                'label' => $field->label,
                'name_key' => $field->name_key,
                'required' => $field->required,
                'options_json' => $field->options_json,
                'sort_order' => $field->sort_order,
            ]);
        }

        $this->auditService->log('template.created', FormTemplate::class, $newTemplate->id);
        return redirect()->route('templates.campos.index', $newTemplate)
            ->with('success', 'Template criado a partir do modelo. Você pode editá-lo livremente.');
    }

    public function store(FormTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['clinic_id'] = $request->user()->clinic_id ?? session('current_clinic_id');
        $data['created_by'] = $request->user()->id;
        $template = FormTemplate::create($data);
        $this->auditService->log('template.created', FormTemplate::class, $template->id);
        return redirect()->route('templates.campos.index', $template)->with('success', 'Template criado.');
    }

    public function edit(FormTemplate $template): View
    {
        $this->authorize('update-template', $template);
        return view('templates.edit', ['template' => $template]);
    }

    public function update(FormTemplateRequest $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $template->update($request->validated());
        $this->auditService->log('template.updated', FormTemplate::class, $template->id);
        return redirect()->route('templates.index')->with('success', 'Template atualizado.');
    }

    public function destroy(FormTemplate $template): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $template->delete();
        $this->auditService->log('template.deleted', FormTemplate::class, $template->id);
        return redirect()->route('templates.index')->with('success', 'Template removido.');
    }

    public function campos(FormTemplate $template): View
    {
        $this->authorize('update-template', $template);
        $template->load('fields');
        return view('templates.campos', ['template' => $template]);
    }

    public function storeCampo(Request $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $data = $request->validate([
            'type' => ['required', 'string', 'in:text,textarea,number,date,select,checkbox,radio,file,signature'],
            'label' => ['required', 'string', 'max:255'],
            'name_key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('form_fields', 'name_key')->where('template_id', $template->id),
            ],
            'required' => ['nullable'],
            'options_text' => ['nullable', 'string'],
        ], [
            'name_key.regex' => 'A chave deve conter apenas letras minúsculas, números e underscore (ex: nome_completo).',
            'name_key.unique' => 'Já existe um campo com esta chave neste template.',
        ]);
        $data['template_id'] = $template->id;
        $data['required'] = (bool) $request->input('required');
        if (! empty($data['options_text'])) {
            $opts = array_filter(array_map('trim', explode("\n", $data['options_text'])));
            $data['options_json'] = ['options' => array_values($opts)];
        }
        unset($data['options_text']);
        $data['sort_order'] = $template->fields()->max('sort_order') + 1;
        FormField::create($data);
        return redirect()->route('templates.campos.index', $template)->with('success', 'Campo adicionado.');
    }

    public function updateCampo(FormFieldRequest $request, FormTemplate $template, FormField $campo): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $data = $request->validated();
        unset($data['options_text']);
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options_json'] = ['options' => array_values($data['options'])];
            unset($data['options']);
        }
        $campo->update($data);
        return redirect()->route('templates.campos.index', $template)->with('success', 'Campo atualizado.');
    }

    public function destroyCampo(FormTemplate $template, FormField $campo): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $campo->delete();
        return redirect()->route('templates.campos.index', $template)->with('success', 'Campo removido.');
    }

    public function gerarLink(FormTemplate $template): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $token = $this->publicLinkService->generateToken($template);
        $url = $this->publicLinkService->getPublicUrl($template);
        return redirect()->route('templates.campos.index', $template)
            ->with('success', 'Link público gerado.')
            ->with('public_url', $url);
    }

    public function desativarLink(FormTemplate $template): RedirectResponse
    {
        $this->authorize('update-template', $template);
        $this->publicLinkService->disablePublicLink($template);
        return redirect()->route('templates.campos.index', $template)->with('success', 'Link público desativado.');
    }
}
