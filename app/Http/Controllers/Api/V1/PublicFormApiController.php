<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Rules\Cpf;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PublicFormApiController extends Controller
{
    public function __construct(private SubmissionService $submissionService) {}

    /**
     * Retorna o template e campos do formulário público para a SPA montar o formulário.
     */
    public function show(string $token): JsonResponse
    {
        $key = 'public-form:' . $token;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['message' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);
        }

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('public_token_expires_at')
                    ->orWhere('public_token_expires_at', '>', now());
            })
            ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        if (! $template) {
            return response()->json(['message' => 'Formulário não encontrado ou não disponível.'], 404);
        }

        $clinic = $template->clinic;

        return response()->json([
            'data' => [
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                ],
                'clinic_name' => $clinic?->name,
                'fields' => $template->fields->map(fn ($f) => [
                    'id' => $f->id,
                    'name_key' => $f->name_key,
                    'label' => $f->label,
                    'type' => $f->type,
                    'required' => $f->required,
                    'options' => $f->options_json ?? [],
                    'sort_order' => $f->sort_order,
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Submete o formulário público (JSON ou multipart com arquivos).
     */
    public function submit(Request $request, string $token): JsonResponse
    {
        $key = 'public-form:' . $token;
        RateLimiter::hit($key, 60);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('public_token_expires_at')
                    ->orWhere('public_token_expires_at', '>', now());
            })
            ->with('fields')
            ->first();

        if (! $template) {
            return response()->json(['message' => 'Formulário não encontrado ou não disponível.'], 404);
        }

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

        $submission = $this->submissionService->createFromPublicForm($template, $data, $files, $signatures, $request);

        return response()->json([
            'data' => [
                'message' => 'Formulário enviado com sucesso.',
                'protocol_number' => $submission->protocol_number,
            ],
        ], 201);
    }
}
