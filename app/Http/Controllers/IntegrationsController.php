<?php

namespace App\Http\Controllers;

use App\Models\ClinicWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            abort(403, 'Selecione uma clínica.');
        }

        $tokens = $request->user()->tokens()->where('name', 'like', 'clinic:' . $clinicId . '-%')->get();
        $webhooks = ClinicWebhook::where('organization_id', $clinicId)->orderBy('created_at', 'desc')->get();
        $deliveries = WebhookDelivery::whereHas('clinicWebhook', fn ($q) => $q->where('organization_id', $clinicId))
            ->with('clinicWebhook')
            ->latest()
            ->limit(50)
            ->get();

        $availableEvents = ['submission.created', 'submission.signed', 'submission.approved', 'submission.rejected'];
        $eventLabels = [
            'submission.created' => 'Submissão criada',
            'submission.signed' => 'Submissão assinada',
            'submission.approved' => 'Submissão aprovada',
            'submission.rejected' => 'Submissão reprovada',
        ];

        return view('clinica.integracoes', [
            'tokens' => $tokens,
            'webhooks' => $webhooks,
            'deliveries' => $deliveries,
            'availableEvents' => $availableEvents,
            'eventLabels' => $eventLabels,
        ]);
    }

    public function createToken(Request $request): RedirectResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return redirect()->route('clinica.integracoes.index')->with('error', 'Selecione uma empresa.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = 'clinic:' . $clinicId . '-' . $validated['name'];
        $token = $request->user()->createToken($name);

        return redirect()->route('clinica.integracoes.index')
            ->with('success', 'Token criado. Guarde-o em local seguro — ele não será exibido novamente.')
            ->with('new_token_plain', $token->plainTextToken)
            ->with('new_token_name', $validated['name']);
    }

    public function revokeToken(Request $request, string $tokenId): RedirectResponse
    {
        $this->authorize('manage-clinic');
        $token = $request->user()->tokens()->findOrFail($tokenId);
        $token->delete();
        return redirect()->route('clinica.integracoes.index')->with('success', 'Token revogado.');
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return redirect()->route('clinica.integracoes.index')->with('error', 'Selecione uma empresa.');
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array'],
            'events.*' => [Rule::in(['submission.created', 'submission.signed', 'submission.approved', 'submission.rejected'])],
            'secret' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ClinicWebhook::create([
            'organization_id' => $clinicId,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => $validated['secret'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('clinica.integracoes.index')->with('success', 'Webhook criado.');
    }

    public function updateWebhook(Request $request, ClinicWebhook $webhook): RedirectResponse
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

        return redirect()->route('clinica.integracoes.index')->with('success', 'Webhook atualizado.');
    }

    public function destroyWebhook(ClinicWebhook $webhook): RedirectResponse
    {
        $this->authorize('manage-clinic');
        if ((string) $webhook->clinic_id !== (string) session('current_clinic_id')) {
            abort(403);
        }
        $webhook->delete();
        return redirect()->route('clinica.integracoes.index')->with('success', 'Webhook removido.');
    }
}
