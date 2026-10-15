<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AvailabilityRule;
use App\Models\Clinic;
use App\Models\FormTemplate;
use App\Models\OrganizationSlugAlias;
use App\Services\PublicLinkService;
use App\Services\ThemeService;
use App\Support\FrontendUrl;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(
        private readonly ThemeService $themeService,
        private readonly PublicLinkService $publicLinkService,
    ) {}

    public function servicesIndex(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $items = AppointmentService::query()
            ->where('organization_id', $orgId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function servicesStore(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'show_price' => ['nullable', 'boolean'],
            'form_template_id' => ['nullable', 'integer', 'exists:form_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $formTemplateId = $this->resolveOrgPublicFormTemplateId($orgId, $data['form_template_id'] ?? null);
        $service = AppointmentService::create([
            'organization_id' => $orgId,
            'name' => $data['name'],
            'duration_minutes' => $data['duration_minutes'],
            'price' => array_key_exists('price', $data) ? $data['price'] : null,
            'show_price' => (bool) ($data['show_price'] ?? false),
            'form_template_id' => $formTemplateId,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json(['data' => $service], 201);
    }

    public function servicesUpdate(Request $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $this->assertServiceOrg($service, $orgId);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'duration_minutes' => ['sometimes', 'integer', 'min:10', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'show_price' => ['nullable', 'boolean'],
            'form_template_id' => ['nullable', 'integer', 'exists:form_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        if (array_key_exists('form_template_id', $data)) {
            $data['form_template_id'] = $this->resolveOrgPublicFormTemplateId($orgId, $data['form_template_id']);
        }
        $service->update($data);

        return response()->json(['data' => $service->fresh()]);
    }

    public function servicesUploadPhoto(Request $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $this->assertServiceOrg($service, $orgId);
        $request->validate(
            [
                'photo' => ['required', 'image', 'max:2048'],
            ],
            [
                'photo.required' => 'Selecione uma imagem para enviar.',
                'photo.image' => 'O arquivo precisa ser uma imagem (JPG, PNG, WebP, etc.).',
                'photo.max' => 'A foto é muito grande. O tamanho máximo permitido é 2 MB.',
            ]
        );

        $file = $request->file('photo');
        if ($service->photo_path) {
            Storage::disk('minio_assets')->delete($service->photo_path);
            Storage::disk('public')->delete($service->photo_path);
        }
        $path = $file->store('organizations/'.$orgId.'/agenda-services', 'minio_assets');
        $service->update(['photo_path' => $path]);

        return response()->json(['data' => $service->fresh()]);
    }

    public function servicesDeletePhoto(Request $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $this->assertServiceOrg($service, $orgId);
        if ($service->photo_path) {
            Storage::disk('minio_assets')->delete($service->photo_path);
            Storage::disk('public')->delete($service->photo_path);
            $service->update(['photo_path' => null]);
        }

        return response()->json(['data' => $service->fresh()]);
    }

    public function servicesDestroy(Request $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $this->assertServiceOrg($service, $orgId);
        if ($service->photo_path) {
            Storage::disk('minio_assets')->delete($service->photo_path);
            Storage::disk('public')->delete($service->photo_path);
        }
        $service->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function availabilityIndex(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $items = AvailabilityRule::query()
            ->where('organization_id', $orgId)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function availabilitySync(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $data = $request->validate([
            'rules' => ['required', 'array', 'max:21'],
            'rules.*.weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'rules.*.start_time' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'rules.*.end_time' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'rules.*.is_active' => ['nullable', 'boolean'],
        ]);

        $saved = DB::transaction(function () use ($orgId, $data) {
            AvailabilityRule::query()->where('organization_id', $orgId)->delete();
            $out = [];
            foreach ($data['rules'] as $rule) {
                $out[] = AvailabilityRule::create([
                    'organization_id' => $orgId,
                    'weekday' => (int) $rule['weekday'],
                    'start_time' => substr((string) $rule['start_time'], 0, 5),
                    'end_time' => substr((string) $rule['end_time'], 0, 5),
                    'is_active' => $rule['is_active'] ?? true,
                ]);
            }

            return $out;
        });

        return response()->json(['data' => $saved]);
    }

    public function appointmentsIndex(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $from = $request->query('from');
        $to = $request->query('to');
        $q = Appointment::query()
            ->with([
                'service:id,name,duration_minutes,form_template_id',
                'template:id,name,public_token,public_enabled',
            ])
            ->where('organization_id', $orgId)
            ->orderBy('starts_at');
        if ($from) {
            $q->where('starts_at', '>=', Carbon::parse((string) $from)->startOfDay());
        }
        if ($to) {
            $q->where('starts_at', '<=', Carbon::parse((string) $to)->endOfDay());
        }
        $items = $q->limit(200)->get()->map(function (Appointment $a) {
            return [
                'id' => $a->id,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'ends_at' => $a->ends_at?->toIso8601String(),
                'status' => $a->status,
                'patient_name' => $a->patient_name,
                'patient_email' => $a->patient_email,
                'patient_phone' => $a->patient_phone,
                'notes' => $a->notes,
                'form_template_id' => $a->form_template_id,
                'form_status' => $a->form_status ?: ($a->form_template_id ? 'pending' : 'not_applicable'),
                'form_template_name' => $a->template?->name,
                'service' => $a->service ? [
                    'id' => $a->service->id,
                    'name' => $a->service->name,
                    'duration_minutes' => $a->service->duration_minutes,
                ] : null,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function appointmentsStore(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date'],
            'patient_name' => ['required', 'string', 'max:160'],
            'patient_email' => ['nullable', 'email', 'max:191'],
            'patient_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'in:confirmed,cancelled,completed'],
        ]);
        $service = AppointmentService::query()
            ->where('organization_id', $orgId)
            ->where('id', $data['service_id'])
            ->first();
        if (! $service) {
            return response()->json(['message' => 'Serviço não encontrado.'], 404);
        }
        $starts = Carbon::parse($data['starts_at']);
        $ends = $starts->copy()->addMinutes(max(10, (int) $service->duration_minutes));
        try {
            $appointment = DB::transaction(function () use ($orgId, $service, $starts, $ends, $data) {
                $overlap = Appointment::query()
                    ->where('organization_id', $orgId)
                    ->where('status', '!=', 'cancelled')
                    ->where('starts_at', '<', $ends)
                    ->where('ends_at', '>', $starts)
                    ->lockForUpdate()
                    ->exists();
                if ($overlap) {
                    throw new \RuntimeException('SLOT_TAKEN');
                }

                $formTemplateId = $service->form_template_id;

                return Appointment::create([
                    'organization_id' => $orgId,
                    'appointment_service_id' => $service->id,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'status' => $data['status'] ?? 'confirmed',
                    'patient_name' => $data['patient_name'],
                    'patient_email' => $data['patient_email'] ?? null,
                    'patient_phone' => $data['patient_phone'] ?? null,
                    'form_template_id' => $formTemplateId,
                    'form_status' => $formTemplateId ? 'pending' : 'not_applicable',
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'SLOT_TAKEN') {
                return response()->json(['message' => 'Horário indisponível.'], 409);
            }
            throw $e;
        }

        return response()->json(['data' => $appointment->load('service:id,name,duration_minutes')], 201);
    }

    public function appointmentsUpdate(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        if ((int) $appointment->organization_id !== $orgId) {
            abort(404);
        }
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'cancelled', 'completed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $appointment->update($data);

        return response()->json(['data' => $appointment->fresh('service:id,name,duration_minutes')]);
    }

    public function publicShow(string $slug): JsonResponse
    {
        $clinic = $this->resolveClinicByPublicSlug($slug);
        if (! $clinic) {
            return response()->json(['message' => 'Agenda não encontrada.'], 404);
        }
        $services = AppointmentService::query()
            ->where('organization_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'form_template_id', 'price', 'show_price', 'photo_path'])
            ->map(static function (AppointmentService $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'form_template_id' => $service->form_template_id,
                    'show_price' => (bool) $service->show_price,
                    'price' => $service->show_price ? $service->price : null,
                    'photo_url' => $service->photo_url,
                ];
            });

        $accentHex = $this->themeService->getPublicAccentHex(
            $clinic->public_theme,
            $clinic->accent_hex,
        );

        return response()->json([
            'data' => [
                'clinic' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                    // Tema da Link Bio (agenda pública herda a identidade visual)
                    'link_bio_model' => $clinic->link_bio_model !== null ? (int) $clinic->link_bio_model : null,
                    'public_theme' => $clinic->public_theme,
                    'accent_hex' => $accentHex,
                ],
                'services' => $services,
            ],
        ]);
    }

    public function publicSlots(Request $request, string $slug): JsonResponse
    {
        $clinic = $this->resolveClinicByPublicSlug($slug);
        if (! $clinic) {
            return response()->json(['message' => 'Agenda não encontrada.'], 404);
        }
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $service = AppointmentService::query()
            ->where('organization_id', $clinic->id)
            ->where('id', $data['service_id'])
            ->where('is_active', true)
            ->first();
        if (! $service) {
            return response()->json(['message' => 'Serviço não encontrado.'], 404);
        }

        $day = Carbon::parse($data['date'])->startOfDay();
        if ($day->lt(now()->startOfDay()) || $day->gt(now()->addDays(60)->endOfDay())) {
            return response()->json(['message' => 'Data fora do intervalo permitido.'], 422);
        }

        $weekday = (int) $day->dayOfWeek; // 0=dom
        $rules = AvailabilityRule::query()
            ->where('organization_id', $clinic->id)
            ->where('weekday', $weekday)
            ->where('is_active', true)
            ->get();
        if ($rules->isEmpty()) {
            return response()->json(['data' => ['slots' => []]]);
        }

        $duration = max(10, (int) $service->duration_minutes);
        $busy = Appointment::query()
            ->where('organization_id', $clinic->id)
            ->where('status', '!=', 'cancelled')
            ->whereDate('starts_at', $day->toDateString())
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        foreach ($rules as $rule) {
            $cursor = Carbon::parse($day->toDateString().' '.$rule->start_time);
            $end = Carbon::parse($day->toDateString().' '.$rule->end_time);
            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotStart->gt(now()->addMinutes(5))) {
                    $overlap = $busy->first(function ($a) use ($slotStart, $slotEnd) {
                        return $a->starts_at < $slotEnd && $a->ends_at > $slotStart;
                    });
                    if (! $overlap) {
                        $slots[] = [
                            'starts_at' => $slotStart->toIso8601String(),
                            'ends_at' => $slotEnd->toIso8601String(),
                        ];
                    }
                }
                $cursor->addMinutes($duration);
            }
        }

        return response()->json(['data' => ['slots' => $slots]]);
    }

    public function publicBook(Request $request, string $slug): JsonResponse
    {
        $clinic = $this->resolveClinicByPublicSlug($slug);
        if (! $clinic) {
            return response()->json(['message' => 'Agenda não encontrada.'], 404);
        }
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date'],
            'patient_name' => ['required', 'string', 'max:160'],
            'patient_email' => ['nullable', 'email', 'max:191'],
            'patient_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if (empty($data['patient_email']) && empty($data['patient_phone'])) {
            return response()->json(['message' => 'Informe e-mail ou telefone.'], 422);
        }

        $service = AppointmentService::query()
            ->where('organization_id', $clinic->id)
            ->where('id', $data['service_id'])
            ->where('is_active', true)
            ->first();
        if (! $service) {
            return response()->json(['message' => 'Serviço não encontrado.'], 404);
        }

        $starts = Carbon::parse($data['starts_at']);
        $ends = $starts->copy()->addMinutes(max(10, (int) $service->duration_minutes));
        if ($starts->lt(now()->addMinutes(5))) {
            return response()->json(['message' => 'Horário inválido.'], 422);
        }

        try {
            $appointment = DB::transaction(function () use ($clinic, $service, $starts, $ends, $data) {
                $overlap = Appointment::query()
                    ->where('organization_id', $clinic->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('starts_at', '<', $ends)
                    ->where('ends_at', '>', $starts)
                    ->lockForUpdate()
                    ->exists();
                if ($overlap) {
                    throw new \RuntimeException('SLOT_TAKEN');
                }

                $formTemplateId = $service->form_template_id;

                return Appointment::create([
                    'organization_id' => $clinic->id,
                    'appointment_service_id' => $service->id,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'status' => 'confirmed',
                    'patient_name' => $data['patient_name'],
                    'patient_email' => $data['patient_email'] ?? null,
                    'patient_phone' => $data['patient_phone'] ?? null,
                    'form_template_id' => $formTemplateId,
                    'form_status' => $formTemplateId ? 'pending' : 'not_applicable',
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'SLOT_TAKEN') {
                return response()->json(['message' => 'Horário indisponível. Escolha outro.'], 409);
            }
            throw $e;
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'unique') || str_contains(strtolower($e->getMessage()), 'duplicate')) {
                return response()->json(['message' => 'Horário indisponível. Escolha outro.'], 409);
            }
            throw $e;
        }

        $formPayload = $this->publicFormInvitePayload($appointment, $request);

        return response()->json([
            'data' => [
                'id' => $appointment->id,
                'public_token' => $appointment->public_token,
                'starts_at' => $appointment->starts_at?->toIso8601String(),
                'ends_at' => $appointment->ends_at?->toIso8601String(),
                'status' => $appointment->status,
                'service_name' => $service->name,
                'form_template_id' => $appointment->form_template_id,
                'form_status' => $appointment->form_status,
                'form' => $formPayload,
            ],
        ], 201);
    }

    private function requireOrg(Request $request): int
    {
        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            abort(422, 'Nenhuma empresa selecionada.');
        }

        return (int) $orgId;
    }

    private function assertServiceOrg(AppointmentService $service, int $orgId): void
    {
        if ((int) $service->organization_id !== $orgId) {
            abort(404);
        }
    }

    /**
     * Aceita ficha ativa da própria organização. Se ainda não tiver link público,
     * publica automaticamente para o paciente poder preencher após a reserva.
     */
    private function resolveOrgPublicFormTemplateId(int $orgId, mixed $rawId): ?int
    {
        if ($rawId === null || $rawId === '' || $rawId === 0 || $rawId === '0') {
            return null;
        }
        $id = (int) $rawId;
        $template = FormTemplate::query()
            ->where('organization_id', $orgId)
            ->where('id', $id)
            ->where('is_active', true)
            ->first();
        if (! $template) {
            throw ValidationException::withMessages([
                'form_template_id' => ['Selecione um modelo ativo da sua clínica.'],
            ]);
        }
        if (! $template->public_enabled || ! $template->public_token) {
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }

        return (int) $template->id;
    }

    /**
     * Convite de ficha na confirmação pública (ou null se o serviço não exige).
     *
     * @return array{name: string, public_url: string, status: string}|null
     */
    private function publicFormInvitePayload(Appointment $appointment, Request $request): ?array
    {
        if (! $appointment->form_template_id || ($appointment->form_status ?? '') === 'not_applicable') {
            return null;
        }
        $template = FormTemplate::withoutGlobalScopes()
            ->where('id', $appointment->form_template_id)
            ->where('organization_id', $appointment->organization_id)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('public_token')
            ->first(['id', 'name', 'public_token']);
        if (! $template || ! $template->public_token) {
            return null;
        }
        $base = FrontendUrl::spaBase($request);
        $url = $base.'/f/'.$template->public_token.'?a='.urlencode((string) $appointment->public_token);

        return [
            'name' => (string) $template->name,
            'public_url' => $url,
            'status' => (string) ($appointment->form_status ?: 'pending'),
        ];
    }

    private function resolveClinicByPublicSlug(string $slug): ?Clinic
    {
        $clinic = Clinic::withoutGlobalScopes()->where('slug', $slug)->first();
        if ($clinic) {
            return $clinic;
        }
        $alias = OrganizationSlugAlias::query()->where('slug', $slug)->first();
        if (! $alias) {
            return null;
        }

        return Clinic::withoutGlobalScopes()->find($alias->organization_id);
    }
}
