<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\FormTemplate;
use App\Rules\Cpf;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function __construct(private SubmissionService $submissionService) {}

    public function show(string $token): View| \Illuminate\Http\RedirectResponse
    {
        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->with(['fields', 'clinic'])
            ->firstOrFail();

        $key = 'public-form:' . $token;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Muitas tentativas. Tente novamente em alguns minutos.');
        }

        return view('formulario-publico.show', ['template' => $template]);
    }

    public function submit(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $key = 'public-form:' . $token;
        RateLimiter::hit($key, 60);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        $rules = [
            '_submitter_name' => ['nullable', 'string', 'max:255'],
            '_submitter_email' => ['nullable', 'email', 'max:255'],
        ];
        foreach ($template->fields as $field) {
            if ($field->required && $field->type !== 'file' && $field->type !== 'signature') {
                $rules[$field->name_key] = ['required'];
            } else {
                $rules[$field->name_key] = ['nullable'];
            }
            if ($field->type === 'file') {
                $rules[$field->name_key] = ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,pdf', 'max:5120'];
            }
            if ($field->name_key === 'cpf' && $field->type === 'text') {
                $rules[$field->name_key][] = new Cpf;
            }
        }
        $validated = $request->validate($rules);

        $data = $validated;
        $files = [];
        foreach ($template->fields as $field) {
            if ($field->type === 'file' && $request->hasFile($field->name_key)) {
                $files[$field->name_key] = $request->file($field->name_key);
            }
        }
        $signatures = $request->input('_signature', []);
        if (! is_array($signatures)) {
            $signatures = $signatures ? ['signature' => $signatures] : [];
        }

        $submission = $this->submissionService->createFromPublicForm($template, $data, $files, $signatures);

        return redirect()->route('formulario-publico.sucesso', ['protocolo' => $submission->protocol_number])
            ->with('protocol_number', $submission->protocol_number)
            ->with('protocol_clinic_id', $submission->clinic_id);
    }

    public function sucesso(Request $request): View
    {
        $protocolNumber = $request->query('protocolo') ?? session('protocol_number');
        $clinicId = session('protocol_clinic_id');
        $clinic = $clinicId ? Clinic::find($clinicId) : null;

        return view('formulario-publico.sucesso', [
            'protocol_number' => $protocolNumber,
            'clinic' => $clinic,
        ]);
    }
}
