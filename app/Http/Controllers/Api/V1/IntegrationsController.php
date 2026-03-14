<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntegrationsController extends Controller
{
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
                'status_code' => $d->status_code,
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
        if ((string) $webhook->clinic_id !== (string) session('current_clinic_id')) {
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
        if ((string) $webhook->clinic_id !== (string) session('current_clinic_id')) {
            abort(403);
        }
        $webhook->delete();

        return response()->json([
            'data' => ['message' => 'Webhook removido.'],
        ], 200);
    }
}
