<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonIndexRequest;
use App\Http\Requests\PersonStoreRequest;
use App\Http\Requests\PersonUpdateRequest;
use App\Http\Resources\Api\V1\PersonResource;
use App\Http\Resources\Api\V1\ProtocolResource;
use App\Models\Person;
use App\Support\ApiPagination;
use App\Support\ApiErrorResponse;
use App\Support\PersonPiiHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonController extends Controller
{
    use ResolvesOrganizationContext;

    public function index(PersonIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Person::query()
            ->withCount('submissions')
            ->withMax('submissions', 'submitted_at')
            ->latest('id');

        if (! empty($validated['search'])) {
            $raw = trim((string) $validated['search']);
            $term = '%'.$raw.'%';
            $digits = preg_replace('/\D+/', '', $raw) ?? '';
            $query->where(function ($w) use ($term, $raw, $digits) {
                $w->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term);
                if (strlen($digits) === 11) {
                    $w->orWhere('cpf_hash', PersonPiiHasher::cpf($digits));
                }
                if (str_contains($raw, '@')) {
                    $w->orWhere('email_hash', PersonPiiHasher::email($raw));
                }
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $hasProtocols = $request->hasProtocolsFilter();
        if ($hasProtocols === true) {
            $query->has('submissions');
        } elseif ($hasProtocols === false) {
            $query->doesntHave('submissions');
        }

        if (! empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }
        if (! empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        $paginator = $query->paginate($request->perPage())->withQueryString();

        return response()->json(
            ApiPagination::wrap($paginator, PersonResource::collection($paginator->items()))
        );
    }

    public function store(PersonStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            return ApiErrorResponse::make('organization_required', 'Nenhuma empresa selecionada.', 422);
        }

        $person = DB::transaction(function () use ($validated, $orgId) {
            $tmpCode = '_tmp_' . bin2hex(random_bytes(8));
            $p = Person::withoutGlobalScopes()->create([
                'organization_id' => $orgId,
                'code' => $tmpCode,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'phone_alt' => $validated['phone_alt'] ?? null,
                'email' => $validated['email'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'age' => $validated['age'] ?? null,
                'sex' => $validated['sex'] ?? null,
                'cpf' => $validated['cpf'] ?? null,
                'rg' => $validated['rg'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'profession' => $validated['profession'] ?? null,
                'referred_by' => $validated['referred_by'] ?? null,
                'address' => $validated['address'] ?? null,
                'neighborhood' => $validated['neighborhood'] ?? null,
                'city' => $validated['city'] ?? null,
                'cep' => $validated['cep'] ?? null,
                'lead_source_instagram' => $validated['lead_source_instagram'] ?? false,
                'lead_source_google' => $validated['lead_source_google'] ?? false,
                'lead_source_facebook' => $validated['lead_source_facebook'] ?? false,
                'lead_source_indicacao_amigo' => $validated['lead_source_indicacao_amigo'] ?? false,
                'lead_source_indicacao_medica' => $validated['lead_source_indicacao_medica'] ?? false,
                'lead_source_plano_saude' => $validated['lead_source_plano_saude'] ?? false,
                'lead_source_outro' => $validated['lead_source_outro'] ?? null,
                'has_health_plan' => $validated['has_health_plan'] ?? null,
                'health_plan_operator' => $validated['health_plan_operator'] ?? null,
                'health_plan_card_number' => $validated['health_plan_card_number'] ?? null,
                'lgpd_accept_comms' => $validated['lgpd_accept_comms'] ?? false,
                'lgpd_accept_reminders' => $validated['lgpd_accept_reminders'] ?? false,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);
            $finalCode = 'P-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT);
            $p->update(['code' => $finalCode]);

            return $p->fresh();
        });

        $person->loadCount('submissions')->loadMax('submissions', 'submitted_at');

        return response()->json([
            'data' => (new PersonResource($person))->exposePii(),
        ], 201);
    }

    public function show(Request $request, Person $pessoa): JsonResponse
    {
        $this->authorize('view-submissions');

        $pessoa->loadCount([
            'submissions',
            'submissions as pending_submissions_count' => fn ($q) => $q->where('status', SubmissionStatus::Pending),
            'submissions as approved_submissions_count' => fn ($q) => $q->where('status', SubmissionStatus::Approved),
            'submissions as rejected_submissions_count' => fn ($q) => $q->where('status', SubmissionStatus::Rejected),
        ]);

        $recent = $pessoa->submissions()
            ->with('template')
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        $pessoa->loadMax('submissions', 'submitted_at');
        $base = (new PersonResource($pessoa))->exposePii()->toArray($request);

        return response()->json([
            'data' => array_merge($base, [
                'stats' => [
                    'protocols_count' => (int) $pessoa->submissions_count,
                    'pending_protocols' => (int) $pessoa->pending_submissions_count,
                    'approved_protocols' => (int) $pessoa->approved_submissions_count,
                    'rejected_protocols' => (int) $pessoa->rejected_submissions_count,
                ],
                'recent_protocols' => ProtocolResource::collection($recent),
            ]),
        ]);
    }

    public function update(PersonUpdateRequest $request, Person $pessoa): JsonResponse
    {
        $validated = $request->validated();

        $pessoa->update($validated);
        $pessoa->loadCount('submissions')->loadMax('submissions', 'submitted_at');

        return response()->json([
            'data' => (new PersonResource($pessoa))->exposePii(),
        ]);
    }

    public function destroy(Request $request, Person $pessoa): JsonResponse
    {
        $this->authorize('people-deactivate');
        $pessoa->update(['status' => 'inactive']);

        return response()->json([
            'data' => ['message' => 'Pessoa inativada.'],
        ]);
    }
}
