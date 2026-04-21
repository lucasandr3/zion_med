<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeegowAppointment;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Person;
use App\Rules\Cpf;
use App\Services\FeegowClient;
use App\Services\SubmissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicFormApiController extends Controller
{
    /** Tamanho máximo por arquivo (KB), alinhado à regra `max:` de campos `file`. */
    private const PUBLIC_FILE_MAX_KB = 5120;

    public function __construct(
        private SubmissionService $submissionService,
        private FeegowClient $feegowClient
    ) {}

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
        $organization = $this->resolveTemplateOrganization($template);
        $feegow = $this->buildFeegowPublicMeta($organization);

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
                'feegow' => $feegow,
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
     * Consulta disponibilidade de horários no Feegow para o formulário público (quando integração ativa).
     */
    public function feegowAvailability(Request $request, string $token): JsonResponse
    {
        $template = FormTemplate::withoutGlobalScopes()
            ->where('public_token', $token)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('public_token_expires_at')
                    ->orWhere('public_token_expires_at', '>', now());
            })
            ->first();

        if (! $template) {
            return response()->json(['message' => 'Formulário não encontrado ou não disponível.'], 404);
        }

        $organization = $this->resolveTemplateOrganization($template);
        if (! $organization || ! $organization->feegow_enabled || ! $organization->feegow_token) {
            return response()->json(['message' => 'Integração Feegow não está ativa para esta empresa.'], 422);
        }

        $validated = $request->validate([
            'tipo' => ['required', 'string', \Illuminate\Validation\Rule::in(['E', 'P'])],
            'especialidade_id' => ['nullable', 'integer'],
            'procedimento_id' => ['nullable', 'integer'],
            'data_start' => ['required', 'date_format:d-m-Y'],
            'data_end' => ['required', 'date_format:d-m-Y'],
            'unidade_id' => ['nullable', 'integer'],
            'profissional_id' => ['nullable', 'integer'],
            'convenio_id' => ['nullable', 'integer'],
            'age_from' => ['nullable', 'integer'],
            'age_to' => ['nullable', 'integer'],
        ]);

        if ($validated['tipo'] === 'E' && empty($validated['especialidade_id'])) {
            return response()->json(['message' => 'Informe especialidade_id quando tipo=E.'], 422);
        }
        if ($validated['tipo'] === 'P' && empty($validated['procedimento_id'])) {
            return response()->json(['message' => 'Informe procedimento_id quando tipo=P.'], 422);
        }

        $tokenValue = trim((string) $organization->feegow_token);
        $baseUrl = trim((string) ($organization->feegow_base_url ?: config('feegow.base_url')));

        try {
            $raw = $this->feegowClient->availableSchedule($tokenValue, $validated, $baseUrl);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => [
                'schedule' => $raw['content'] ?? [],
                'raw' => $raw,
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

        $organization = $this->resolveTemplateOrganization($template);
        $feegowResult = $this->tryCreateFeegowAppointmentFromPublicForm(
            $organization,
            $personId ? Person::withoutGlobalScopes()->find($personId) : null,
            $data
        );

        return response()->json([
            'data' => [
                'message' => 'Formulário enviado com sucesso.',
                'protocol_number' => $submission->protocol_number,
                'feegow' => $feegowResult,
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

        $tmp = tempnam(sys_get_temp_dir(), 'gestgo_pub_');
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

    private function resolveTemplateOrganization(FormTemplate $template): ?Organization
    {
        $orgId = $template->organization_id ?? $template->clinic_id ?? null;
        if (! $orgId) {
            return null;
        }

        return Organization::query()->find($orgId);
    }

    /**
     * @return array{
     *   enabled: bool,
     *   requires_fields?: list<string>,
     *   specialties?: array<int, mixed>,
     *   insurances?: array<int, mixed>,
     *   units?: array<int, mixed>,
     *   locals?: array<int, mixed>,
     *   channels?: array<int, mixed>,
     *   warning?: string
     * }
     */
    private function buildFeegowPublicMeta(?Organization $organization): array
    {
        if (! $organization || ! $organization->feegow_enabled || ! $organization->feegow_token) {
            return ['enabled' => false];
        }

        $token = trim((string) $organization->feegow_token);
        $baseUrl = trim((string) ($organization->feegow_base_url ?: config('feegow.base_url')));
        if ($token === '' || $baseUrl === '') {
            return ['enabled' => false];
        }

        $meta = [
            'enabled' => true,
            'requires_fields' => [
                'feegow_paciente_id',
                'feegow_profissional_id',
                'feegow_procedimento_id',
                'feegow_local_id',
                'feegow_especialidade_id',
                'feegow_data',
                'feegow_horario',
            ],
        ];

        try {
            $specialties = $this->feegowClient->listSpecialties($token, null, $baseUrl);
            $insurances = $this->feegowClient->listInsurances($token, null, $baseUrl);
            $units = $this->feegowClient->listUnits($token, $baseUrl);
            $locals = $this->feegowClient->listLocals($token, $baseUrl);
            $channels = $this->feegowClient->listAppointmentChannels($token, $baseUrl);

            $meta['specialties'] = is_array($specialties['content'] ?? null) ? $specialties['content'] : [];
            $meta['insurances'] = is_array($insurances['content'] ?? null) ? $insurances['content'] : [];
            $meta['units'] = is_array($units['content'] ?? null) ? $units['content'] : [];
            $meta['locals'] = is_array($locals['content'] ?? null) ? $locals['content'] : [];
            $meta['channels'] = is_array($channels['content'] ?? null) ? $channels['content'] : [];
        } catch (\Throwable $e) {
            $meta['warning'] = 'Catálogos Feegow indisponíveis no momento.';
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{enabled: bool, attempted: bool, created: bool, feegow_appointment_id?: int|null, message?: string}
     */
    private function tryCreateFeegowAppointmentFromPublicForm(?Organization $organization, ?Person $person, array $data): array
    {
        if (! $organization || ! $organization->feegow_enabled || ! $organization->feegow_token) {
            return ['enabled' => false, 'attempted' => false, 'created' => false];
        }

        $payload = $this->buildFeegowAppointmentPayload($data);
        if (! $payload) {
            return [
                'enabled' => true,
                'attempted' => false,
                'created' => false,
                'message' => 'Campos Feegow não informados; envio local concluído sem criar agendamento externo.',
            ];
        }

        $token = trim((string) $organization->feegow_token);
        $baseUrl = trim((string) ($organization->feegow_base_url ?: config('feegow.base_url')));

        try {
            $response = $this->feegowClient->createAppointment($token, $payload, $baseUrl);
            $feegowAppointmentId = $response['content']['agendamento_id'] ?? null;

            if (is_numeric($feegowAppointmentId)) {
                FeegowAppointment::create([
                    'organization_id' => $organization->id,
                    'person_id' => $person?->id,
                    'feegow_appointment_id' => (int) $feegowAppointmentId,
                    'status' => 'created',
                    'request_payload' => $payload,
                    'response_payload' => $response,
                    'external_reference' => is_string($data['feegow_external_reference'] ?? null)
                        ? mb_substr((string) $data['feegow_external_reference'], 0, 120)
                        : null,
                ]);

                return [
                    'enabled' => true,
                    'attempted' => true,
                    'created' => true,
                    'feegow_appointment_id' => (int) $feegowAppointmentId,
                ];
            }

            return [
                'enabled' => true,
                'attempted' => true,
                'created' => false,
                'message' => 'Feegow não retornou agendamento_id.',
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => true,
                'attempted' => true,
                'created' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function buildFeegowAppointmentPayload(array $data): ?array
    {
        $pacienteId = $this->toInt($data['feegow_paciente_id'] ?? null);
        $profissionalId = $this->toInt($data['feegow_profissional_id'] ?? null);
        $procedimentoId = $this->toInt($data['feegow_procedimento_id'] ?? null);
        $especialidadeId = $this->toInt($data['feegow_especialidade_id'] ?? null);
        $localId = $this->toInt($data['feegow_local_id'] ?? null);
        $dataAgendamento = $this->normalizeFeegowDate($data['feegow_data'] ?? null);
        $horario = $this->normalizeFeegowTime($data['feegow_horario'] ?? null);

        if (! $pacienteId || ! $profissionalId || ! $procedimentoId || ! $especialidadeId || ! $localId || ! $dataAgendamento || ! $horario) {
            return null;
        }

        $payload = [
            'local_id' => $localId,
            'paciente_id' => $pacienteId,
            'profissional_id' => $profissionalId,
            'especialidade_id' => $especialidadeId,
            'procedimento_id' => $procedimentoId,
            'data' => $dataAgendamento,
            'horario' => $horario,
        ];

        $optionalMap = [
            'valor' => 'feegow_valor',
            'plano' => 'feegow_plano',
            'convenio_id' => 'feegow_convenio_id',
            'convenio_plano_id' => 'feegow_convenio_plano_id',
            'canal_id' => 'feegow_canal_id',
            'tabela_id' => 'feegow_tabela_id',
            'notas' => 'feegow_notas',
            'celular' => 'feegow_celular',
            'telefone' => 'feegow_telefone',
            'email' => 'feegow_email',
            'retorno' => 'feegow_retorno',
            'sys_user' => 'feegow_sys_user',
        ];

        foreach ($optionalMap as $target => $source) {
            if (! array_key_exists($source, $data) || $data[$source] === null || $data[$source] === '') {
                continue;
            }

            if (in_array($target, ['valor'], true)) {
                $payload[$target] = (float) $data[$source];
                continue;
            }
            if (in_array($target, ['plano', 'convenio_id', 'convenio_plano_id', 'canal_id', 'tabela_id', 'sys_user'], true)) {
                $iv = $this->toInt($data[$source]);
                if ($iv !== null) {
                    $payload[$target] = $iv;
                }
                continue;
            }
            if ($target === 'retorno') {
                $payload[$target] = filter_var($data[$source], FILTER_VALIDATE_BOOL);
                continue;
            }

            $payload[$target] = (string) $data[$source];
        }

        return $payload;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeFeegowDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $raw = trim($value);
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $raw);
                if ($d !== false) {
                    return $d->format('d-m-Y');
                }
            } catch (\Throwable) {
                // tenta próximo formato
            }
        }

        return null;
    }

    private function normalizeFeegowTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $raw = trim($value);
        foreach (['H:i:s', 'H:i'] as $fmt) {
            try {
                $t = Carbon::createFromFormat($fmt, $raw);
                if ($t !== false) {
                    return $t->format('H:i:s');
                }
            } catch (\Throwable) {
                // tenta próximo formato
            }
        }

        return null;
    }
}
