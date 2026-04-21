<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhookJob;
use App\Models\ClinicWebhook;
use App\Models\FeegowAppointment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\WebhookDelivery;
use App\Services\FeegowClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntegrationsController extends Controller
{
    public function __construct(
        private readonly FeegowClient $feegow
    ) {}

    /**
     * Lista tokens e webhooks da clínica (para integrações).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return response()->json(['message' => 'Selecione uma clínica.'], 422);
        }

        $tokens = $request->user()->tokens()->where('name', 'like', 'clinic:' . $clinicId . '-%')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => \Illuminate\Support\Str::after($t->name, 'clinic:' . $clinicId . '-'),
            'created_at' => $t->created_at?->toIso8601String(),
        ]);

        $webhooks = ClinicWebhook::where('organization_id', $clinicId)->orderByDesc('created_at')->get()->map(fn ($w) => [
            'id' => $w->id,
            'url' => $w->url,
            'events' => $w->events,
            'description' => $w->description,
            'is_active' => $w->is_active,
            'created_at' => $w->created_at?->toIso8601String(),
        ]);

        $deliveries = WebhookDelivery::whereHas('clinicWebhook', fn ($q) => $q->where('organization_id', $clinicId))
            ->with('clinicWebhook')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'webhook_id' => $d->clinic_webhook_id,
                'event' => $d->event,
                'response_code' => $d->response_code,
                'error_message' => $d->error_message,
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        $availableEvents = ['submission.created', 'submission.signed', 'submission.approved', 'submission.rejected'];
        $eventLabels = [
            'submission.created' => 'Submissão criada',
            'submission.signed' => 'Submissão assinada',
            'submission.approved' => 'Submissão aprovada',
            'submission.rejected' => 'Submissão reprovada',
        ];

        return response()->json([
            'data' => [
                'tokens' => $tokens,
                'webhooks' => $webhooks,
                'deliveries' => $deliveries,
                'available_events' => $availableEvents,
                'event_labels' => $eventLabels,
            ],
        ]);
    }

    /**
     * Cria um token de API (o valor retornado deve ser guardado pelo cliente; não é exibido novamente).
     */
    public function createToken(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = 'clinic:' . $clinicId . '-' . $validated['name'];
        $token = $request->user()->createToken($name);

        return response()->json([
            'data' => [
                'message' => 'Token criado. Guarde-o em local seguro — ele não será exibido novamente.',
                'token' => $token->plainTextToken,
                'token_id' => $token->accessToken->id,
                'name' => $validated['name'],
            ],
        ], 201);
    }

    /**
     * Revoga um token.
     */
    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $this->authorize('manage-clinic');
        $token = $request->user()->tokens()->findOrFail($tokenId);
        $token->delete();

        return response()->json([
            'data' => ['message' => 'Token revogado.'],
        ], 200);
    }

    public function storeWebhook(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array'],
            'events.*' => [Rule::in(['submission.created', 'submission.signed', 'submission.approved', 'submission.rejected'])],
            'secret' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $webhook = ClinicWebhook::create([
            'organization_id' => $clinicId,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => $validated['secret'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'description' => $webhook->description,
                'is_active' => $webhook->is_active,
                'created_at' => $webhook->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function updateWebhook(Request $request, ClinicWebhook $webhook): JsonResponse
    {
        $this->authorize('manage-clinic');
        if ((string) $webhook->organization_id !== (string) session('current_clinic_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array'],
            'events.*' => [Rule::in(['submission.created', 'submission.signed', 'submission.approved', 'submission.rejected'])],
            'secret' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $webhook->update([
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => $validated['secret'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'description' => $webhook->description,
                'is_active' => $webhook->is_active,
            ],
        ]);
    }

    public function destroyWebhook(ClinicWebhook $webhook): JsonResponse
    {
        $this->authorize('manage-clinic');
        if ((string) $webhook->organization_id !== (string) session('current_clinic_id')) {
            abort(403);
        }
        $webhook->delete();

        return response()->json([
            'data' => ['message' => 'Webhook removido.'],
        ], 200);
    }

    /**
     * Reenvia uma entrega de webhook falha (retry).
     */
    public function retryWebhookDelivery(Request $request, WebhookDelivery $delivery): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return response()->json(['message' => 'Selecione uma clínica.'], 422);
        }

        $delivery->load('clinicWebhook');
        if (! $delivery->clinicWebhook || (string) $delivery->clinicWebhook->organization_id !== (string) $clinicId) {
            abort(404);
        }

        $payload = $delivery->payload;
        if (is_array($payload)) {
            unset($payload['event']);
        } else {
            $payload = [];
        }

        DispatchWebhookJob::dispatch($delivery->clinicWebhook, $delivery->event, $payload);

        return response()->json([
            'data' => ['message' => 'Reenvio do webhook agendado.'],
        ]);
    }

    public function systemsIndex(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $status = $this->resolveFeegowStatus($organization);

        return response()->json([
            'data' => [
                [
                    'key' => 'feegow',
                    'name' => 'Feegow',
                    'description' => 'Integração com API Feegow (x-access-token).',
                    'enabled' => (bool) $organization->feegow_enabled,
                    'status' => $status,
                    'last_check_at' => $organization->feegow_last_check_at?->toIso8601String(),
                    'last_error' => $organization->feegow_last_error,
                ],
            ],
        ]);
    }

    public function feegowShow(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        return response()->json([
            'data' => [
                'enabled' => (bool) $organization->feegow_enabled,
                'base_url' => $organization->feegow_base_url ?: config('feegow.base_url'),
                'has_token' => ! empty($organization->feegow_token),
                'status' => $this->resolveFeegowStatus($organization),
                'last_check_at' => $organization->feegow_last_check_at?->toIso8601String(),
                'last_error' => $organization->feegow_last_error,
            ],
        ]);
    }

    public function feegowUpdate(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'token' => ['nullable', 'string', 'max:4096'],
        ]);

        $enabled = (bool) $validated['enabled'];
        $baseUrl = $this->feegow->normalizeBaseUrl((string) ($validated['base_url'] ?? config('feegow.base_url')));
        $incomingToken = isset($validated['token']) ? trim((string) $validated['token']) : null;
        $hasStoredToken = ! empty($organization->feegow_token);

        if ($enabled && ! $hasStoredToken && ($incomingToken === null || $incomingToken === '')) {
            return response()->json(['message' => 'Informe o token do Feegow para ativar a integração.'], 422);
        }

        $updates = [
            'feegow_enabled' => $enabled,
            'feegow_base_url' => $baseUrl,
        ];

        if ($incomingToken !== null) {
            $updates['feegow_token'] = $incomingToken !== '' ? $incomingToken : null;
        }

        if (! $enabled) {
            $updates['feegow_last_status'] = 'disabled';
            $updates['feegow_last_error'] = null;
        }

        $organization->forceFill($updates)->save();

        return $this->feegowShow($request);
    }

    public function feegowTest(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $token = is_string($organization->feegow_token) ? $organization->feegow_token : '';
        $baseUrl = is_string($organization->feegow_base_url) && $organization->feegow_base_url !== ''
            ? $organization->feegow_base_url
            : (string) config('feegow.base_url');

        if ($token === '') {
            return response()->json(['message' => 'Token do Feegow não configurado.'], 422);
        }

        try {
            $this->feegow->ping($token, $baseUrl);
            $organization->forceFill([
                'feegow_last_check_at' => now(),
                'feegow_last_status' => 'ok',
                'feegow_last_error' => null,
            ])->save();

            return response()->json([
                'data' => [
                    'ok' => true,
                    'status' => 'ok',
                    'message' => 'Conexão com Feegow validada com sucesso.',
                    'last_check_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            $organization->forceFill([
                'feegow_last_check_at' => now(),
                'feegow_last_status' => 'error',
                'feegow_last_error' => mb_substr($e->getMessage(), 0, 600),
            ])->save();

            return response()->json([
                'data' => [
                    'ok' => false,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'last_check_at' => now()->toIso8601String(),
                ],
            ], 502);
        }
    }

    public function feegowCatalogs(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        [$token, $baseUrl, $error] = $this->feegowCredentials($organization);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $unidadeId = $request->filled('unidade_id') ? (int) $request->query('unidade_id') : null;

        try {
            $specialtiesRaw = $this->feegow->listSpecialties($token, $unidadeId, $baseUrl);
            $insurancesRaw = $this->feegow->listInsurances($token, $unidadeId, $baseUrl);
            $unitsRaw = $this->feegow->listUnits($token, $baseUrl);
            $localsRaw = $this->feegow->listLocals($token, $baseUrl);
            $channelsRaw = $this->feegow->listAppointmentChannels($token, $baseUrl);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => [
                'specialties' => $specialtiesRaw['content'] ?? [],
                'insurances' => $insurancesRaw['content'] ?? [],
                'units' => is_array($unitsRaw['content'] ?? null) ? $unitsRaw['content'] : [],
                'locals' => $localsRaw['content'] ?? [],
                'channels' => $channelsRaw['content'] ?? [],
            ],
        ]);
    }

    public function feegowAvailableSchedule(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        [$token, $baseUrl, $error] = $this->feegowCredentials($organization);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $validated = $request->validate([
            'tipo' => ['required', 'string', Rule::in(['E', 'P'])],
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

        try {
            $raw = $this->feegow->availableSchedule($token, $validated, $baseUrl);
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

    public function feegowCreateAppointment(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        [$token, $baseUrl, $error] = $this->feegowCredentials($organization);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $validated = $request->validate([
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'external_reference' => ['nullable', 'string', 'max:120'],

            'local_id' => ['required', 'integer'],
            'paciente_id' => ['required', 'integer'],
            'profissional_id' => ['required', 'integer'],
            'especialidade_id' => ['required', 'integer'],
            'procedimento_id' => ['required', 'integer'],
            'data' => ['required', 'date_format:d-m-Y'],
            'horario' => ['required', 'date_format:H:i:s'],

            'valor' => ['nullable', 'numeric'],
            'plano' => ['nullable', 'integer'],
            'convenio_id' => ['nullable', 'integer'],
            'convenio_plano_id' => ['nullable', 'integer'],
            'canal_id' => ['nullable', 'integer'],
            'tabela_id' => ['nullable', 'integer'],
            'notas' => ['nullable', 'string'],
            'celular' => ['nullable', 'string', 'max:40'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'retorno' => ['nullable', 'boolean'],
            'sys_user' => ['nullable', 'integer'],
        ]);

        $person = null;
        if (! empty($validated['person_id'])) {
            $person = Person::withoutGlobalScopes()->find($validated['person_id']);
            if (! $person || (int) $person->organization_id !== (int) $organization->id) {
                return response()->json(['message' => 'Pessoa não pertence à empresa atual.'], 422);
            }
        }

        $payload = collect($validated)
            ->except(['person_id', 'external_reference'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        try {
            $response = $this->feegow->createAppointment($token, $payload, $baseUrl);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $feegowAppointmentId = $response['content']['agendamento_id'] ?? null;
        if (! is_numeric($feegowAppointmentId)) {
            return response()->json([
                'message' => 'Feegow não retornou agendamento_id após criação.',
                'data' => ['raw' => $response],
            ], 502);
        }

        $record = FeegowAppointment::create([
            'organization_id' => $organization->id,
            'person_id' => $person?->id,
            'feegow_appointment_id' => (int) $feegowAppointmentId,
            'status' => 'created',
            'request_payload' => $payload,
            'response_payload' => $response,
            'external_reference' => $validated['external_reference'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'message' => 'Agendamento criado no Feegow e vínculo salvo localmente.',
                'feegow_appointment_id' => (int) $feegowAppointmentId,
                'integration_record_id' => $record->id,
                'raw' => $response,
            ],
        ], 201);
    }

    private function currentOrganization(Request $request): ?Organization
    {
        $organizationId = session('current_organization_id') ?? session('current_clinic_id');
        if (! $organizationId) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

    private function resolveFeegowStatus(Organization $organization): string
    {
        if (! $organization->feegow_enabled) {
            return 'disabled';
        }
        if (! $organization->feegow_token) {
            return 'not_configured';
        }

        return match ($organization->feegow_last_status) {
            'ok' => 'ok',
            'error' => 'error',
            default => 'unknown',
        };
    }

    /**
     * @return array{0:string,1:string,2:?string}
     */
    private function feegowCredentials(Organization $organization): array
    {
        if (! $organization->feegow_enabled) {
            return ['', '', 'Integração Feegow está desativada para esta empresa.'];
        }

        $token = is_string($organization->feegow_token) ? trim($organization->feegow_token) : '';
        $baseUrl = is_string($organization->feegow_base_url) && trim($organization->feegow_base_url) !== ''
            ? trim($organization->feegow_base_url)
            : (string) config('feegow.base_url');

        if ($token === '') {
            return ['', '', 'Token do Feegow não configurado.'];
        }
        if (trim($baseUrl) === '') {
            return ['', '', 'Base URL do Feegow não configurada.'];
        }

        return [$token, $baseUrl, null];
    }
}
