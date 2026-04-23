<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PersonResource;
use App\Http\Resources\Api\V1\ProtocolResource;
use App\Models\Person;
use App\Support\PersonPiiHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');

        $query = Person::query()
            ->withCount('submissions')
            ->withMax('submissions', 'submitted_at')
            ->latest('id');

        if ($request->filled('search')) {
            $raw = trim((string) $request->search);
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('has_protocols')) {
            $v = $request->query('has_protocols');
            if ($v === '1' || $v === 'true' || $v === true) {
                $query->has('submissions');
            } elseif ($v === '0' || $v === 'false' || $v === false) {
                $query->doesntHave('submissions');
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => PersonResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $orgId = session('current_clinic_id') ?? $request->user()?->organization_id ?? $request->user()?->clinic_id;
        if (! $orgId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $person = DB::transaction(function () use ($validated, $orgId) {
            $tmpCode = '_tmp_' . bin2hex(random_bytes(8));
            $p = Person::withoutGlobalScopes()->create([
                'organization_id' => $orgId,
                'code' => $tmpCode,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'cpf' => $validated['cpf'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);
            $finalCode = 'P-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT);
            $p->update(['code' => $finalCode]);

            return $p->fresh();
        });

        $person->loadCount('submissions')->loadMax('submissions', 'submitted_at');

        return response()->json([
            'data' => new PersonResource($person),
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
        $base = (new PersonResource($pessoa))->toArray($request);

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

    public function update(Request $request, Person $pessoa): JsonResponse
    {
        $this->authorize('view-submissions');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $pessoa->update($validated);
        $pessoa->loadCount('submissions')->loadMax('submissions', 'submitted_at');

        return response()->json([
            'data' => new PersonResource($pessoa),
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
