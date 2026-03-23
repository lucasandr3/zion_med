<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Rules\Cpf;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicFormApiController extends Controller
{
    /** Tamanho máximo por arquivo (KB), alinhado à regra `max:` de campos `file`. */
    private const PUBLIC_FILE_MAX_KB = 5120;

    public function __construct(private SubmissionService $submissionService) {}

    /**
     * Retorna o template e campos do formulário público para a SPA montar o formulário.
     */
    public function show(string $token): JsonResponse
    {
        $key = 'public-form:'.$token;
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
        $key = 'public-form-person:'.$token;
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
        $key = 'public-form:'.$token;
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
                $rules[$field->name_key] = ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,pdf', 'max:'.self::PUBLIC_FILE_MAX_KB];
            }
            if ($field->name_key === 'cpf' && $field->type === 'text') {
                $rules[$field->name_key][] = new Cpf;
            }
        }

        $this->hydrateFileFieldsFromJsonPayload($request, $template);

        $validated = $request->validate($rules);
        $data = $validated;

        foreach ($template->fields as $field) {
            if ($field->type === 'file' && $field->required && ! $request->hasFile($field->name_key)) {
                throw ValidationException::withMessages([
                    $field->name_key => ['O campo '.$field->label.' é obrigatório.'],
                ]);
            }
        }

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

    /**
     * Quando a SPA envia JSON com foto em Base64 ou data URL (em vez de multipart),
     * converte para {@see UploadedFile} no bag de arquivos para as regras `file` funcionarem.
     */
    private function hydrateFileFieldsFromJsonPayload(Request $request, FormTemplate $template): void
    {
        $touchedFilesBag = false;

        foreach ($template->fields as $field) {
            if ($field->type !== 'file') {
                continue;
            }
            $key = $field->name_key;
            if ($request->hasFile($key)) {
                continue;
            }
            $raw = $request->input($key);
            if ($raw === null) {
                continue;
            }
            if (! is_string($raw)) {
                throw ValidationException::withMessages([
                    $key => ['O campo '.$field->label.' deve ser um arquivo ou imagem em Base64.'],
                ]);
            }
            if (trim($raw) === '') {
                $request->request->remove($key);

                continue;
            }
            $uploaded = $this->uploadedFileFromBase64OrDataUrl(trim($raw), $field->label, $key);
            $request->files->add([$key => $uploaded]);
            $request->request->remove($key);
            $touchedFilesBag = true;
        }

        if ($touchedFilesBag) {
            $this->flushUploadedFilesCache($request);
        }
    }

    /** Limpa o cache interno de arquivos convertidos do Request (JSON + Base64 após o parse inicial). */
    private function flushUploadedFilesCache(Request $request): void
    {
        $prop = new \ReflectionProperty(Request::class, 'convertedFiles');
        $prop->setValue($request, null);
    }

    /**
     * @throws ValidationException
     */
    private function uploadedFileFromBase64OrDataUrl(string $raw, string $fieldLabel, string $fieldKey): UploadedFile
    {
        $maxBytes = self::PUBLIC_FILE_MAX_KB * 1024;
        $maxPayloadChars = (int) ceil($maxBytes * 4 / 3) + 512;

        if (strlen($raw) > $maxPayloadChars) {
            throw ValidationException::withMessages([
                $fieldKey => ['O arquivo enviado em '.$fieldLabel.' é grande demais (máx. 5 MB).'],
            ]);
        }

        if (preg_match('/^data:([\w\/.+-]+);base64,(.+)$/is', $raw, $matches)) {
            $b64 = preg_replace('/\s+/', '', $matches[2]) ?? $matches[2];
        } else {
            $b64 = preg_replace('/\s+/', '', $raw) ?? $raw;
        }

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                $fieldKey => ['O campo '.$fieldLabel.' não é um Base64 válido.'],
            ]);
        }

        if (strlen($binary) > $maxBytes) {
            throw ValidationException::withMessages([
                $fieldKey => ['O arquivo enviado em '.$fieldLabel.' é grande demais (máx. 5 MB).'],
            ]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'zion_pub_');
        if ($tmp === false) {
            throw ValidationException::withMessages([
                $fieldKey => ['Não foi possível processar o arquivo de '.$fieldLabel.'.'],
            ]);
        }

        file_put_contents($tmp, $binary);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: 'application/octet-stream';

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (! in_array($mime, $allowed, true)) {
            unlink($tmp);
            throw ValidationException::withMessages([
                $fieldKey => ['O arquivo de '.$fieldLabel.' deve ser imagem (JPEG, PNG, GIF) ou PDF.'],
            ]);
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            default => 'bin',
        };

        return new UploadedFile($tmp, $fieldKey.'.'.$extension, $mime, UPLOAD_ERR_OK, true);
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
