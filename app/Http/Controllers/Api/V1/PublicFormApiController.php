<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Rules\Cpf;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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
                'logo_url' => $clinic?->logo_url,
                'person_link' => [
                    'enabled' => (bool) $template->public_require_person_link,
                    'mode' => $template->public_require_person_link ? 'code_birth_date' : 'none',
                    'title' => 'Identifique-se para continuar',
                    'description' => 'Informe seu código de acesso e sua data de nascimento para vincular esta resposta à sua ficha.',
                ],
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
     * Valida código + data de nascimento antes de liberar o formulário (quando o template exige vínculo).
     */
    public function validatePerson(Request $request, string $token): JsonResponse
    {
        $key = 'public-form-person:' . $token;
        if (RateLimiter::tooManyAttempts($key, 40)) {
            return response()->json(['message' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);
        }
        RateLimiter::hit($key, 120);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('public_token_expires_at')
                    ->orWhere('public_token_expires_at', '>', now());
            })
            ->first();

        if (! $template || ! $template->public_require_person_link) {
            return response()->json(['message' => 'Validação não necessária para este formulário.'], 422);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'birth_date' => ['required', 'date'],
        ]);

        $person = $this->findPersonForTemplate($template, $validated['code'], $validated['birth_date']);
        if (! $person) {
            throw ValidationException::withMessages([
                'code' => ['Código ou data de nascimento não conferem.'],
            ]);
        }

        return response()->json([
            'data' => [
                'person_id' => $person->id,
                'code' => $person->code,
                'name' => $person->name,
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
        if ($template->public_require_person_link) {
            $rules['_person_code'] = ['required', 'string', 'max:32'];
            $rules['_person_birth_date'] = ['required', 'date'];
        } else {
            $rules['_person_code'] = ['nullable', 'string', 'max:32'];
            $rules['_person_birth_date'] = ['nullable', 'date'];
        }
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

        $personId = null;
        if ($template->public_require_person_link) {
            $person = $this->findPersonForTemplate(
                $template,
                (string) $data['_person_code'],
                (string) $data['_person_birth_date']
            );
            if (! $person || $person->status !== 'active') {
                throw ValidationException::withMessages([
                    '_person_code' => ['Código ou data de nascimento não conferem.'],
                ]);
            }
            $personId = $person->id;
        }

        unset($data['_person_code'], $data['_person_birth_date']);

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

        $submission = $this->submissionService->createFromPublicForm($template, $data, $files, $signatures, $request, $personId);

        return response()->json([
            'data' => [
                'message' => 'Formulário enviado com sucesso.',
                'protocol_number' => $submission->protocol_number,
            ],
        ], 201);
    }

    private function findPersonForTemplate(FormTemplate $template, string $code, string $birthDate): ?Person
    {
        $orgId = $template->organization_id ?? $template->clinic_id;
        $codeNorm = strtoupper(trim($code));
        $birth = \Carbon\Carbon::parse($birthDate)->format('Y-m-d');

        return Person::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->whereRaw('UPPER(TRIM(code)) = ?', [$codeNorm])
            ->whereDate('birth_date', $birth)
            ->where('status', 'active')
            ->first();
    }
}
