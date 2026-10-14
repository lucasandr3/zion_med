<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\AvailabilityRule;
use App\Models\Clinic;
use App\Models\OrganizationSlugAlias;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    use ResolvesOrganizationContext;

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
            'form_template_id' => ['nullable', 'integer', 'exists:form_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $service = AppointmentService::create([
            'organization_id' => $orgId,
            'name' => $data['name'],
            'duration_minutes' => $data['duration_minutes'],
            'form_template_id' => $data['form_template_id'] ?? null,
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
            'form_template_id' => ['nullable', 'integer', 'exists:form_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $service->update($data);

        return response()->json(['data' => $service->fresh()]);
    }

    public function servicesDestroy(Request $request, AppointmentService $service): JsonResponse
    {
        $this->authorize('manage-clinic');
        $orgId = $this->requireOrg($request);
        $this->assertServiceOrg($service, $orgId);
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
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i'],
            'rules.*.is_active' => ['nullable', 'boolean'],
        ]);

        $saved = DB::transaction(function () use ($orgId, $data) {
            AvailabilityRule::query()->where('organization_id', $orgId)->delete();
            $out = [];
            foreach ($data['rules'] as $rule) {
                $out[] = AvailabilityRule::create([
                    'organization_id' => $orgId,
                    'weekday' => (int) $rule['weekday'],
                    'start_time' => $rule['start_time'],
                    'end_time' => $rule['end_time'],
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
            ->with('service:id,name,duration_minutes')
            ->where('organization_id', $orgId)
            ->orderBy('starts_at');
        if ($from) {
            $q->where('starts_at', '>=', Carbon::parse((string) $from)->startOfDay());
        }
        if ($to) {
            $q->where('starts_at', '<=', Carbon::parse((string) $to)->endOfDay());
        }
        $items = $q->limit(200)->get();

        return response()->json(['data' => $items]);
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
            ->get(['id', 'name', 'duration_minutes', 'form_template_id']);

        return response()->json([
            'data' => [
                'clinic' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
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

                return Appointment::create([
                    'organization_id' => $clinic->id,
                    'appointment_service_id' => $service->id,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'status' => 'confirmed',
                    'patient_name' => $data['patient_name'],
                    'patient_email' => $data['patient_email'] ?? null,
                    'patient_phone' => $data['patient_phone'] ?? null,
                    'form_template_id' => $service->form_template_id,
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

        return response()->json([
            'data' => [
                'id' => $appointment->id,
                'public_token' => $appointment->public_token,
                'starts_at' => $appointment->starts_at?->toIso8601String(),
                'ends_at' => $appointment->ends_at?->toIso8601String(),
                'status' => $appointment->status,
                'service_name' => $service->name,
                'form_template_id' => $appointment->form_template_id,
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
