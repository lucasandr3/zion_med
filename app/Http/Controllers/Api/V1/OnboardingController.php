<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormTemplate;
use App\Services\DocumentSendService;
use App\Services\ProductEventService;
use App\Services\PublicLinkService;
use App\Services\TemplateVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly TemplateVersionService $templateVersionService,
        private readonly PublicLinkService $publicLinkService,
        private readonly DocumentSendService $documentSendService,
        private readonly ProductEventService $productEvents,
    ) {}

    /**
     * Lista até 3 modelos relevantes ao nicho da organização (wizard pós-cadastro).
     */
    public function templates(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');

        $org = $request->user()?->clinic;
        $niche = (string) ($org?->niche ?: 'estetica');

        $items = FormTemplate::query()
            ->where('is_active', true)
            ->visibleForNiche($niche)
            ->orderBy('name')
            ->limit(3)
            ->get();
        if ($items->isEmpty()) {
            $items = FormTemplate::query()->where('is_active', true)->orderBy('name')->limit(3)->get();
        }

        return response()->json([
            'data' => TemplateResource::collection($items),
        ]);
    }

    /**
     * Gera link público do modelo escolhido no onboarding.
     */
    public function gerarLink(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);
        $this->templateVersionService->getOrCreateCurrentVersion($template);
        $this->publicLinkService->generateToken($template);

        $orgId = (int) ($template->organization_id ?? $template->clinic_id);
        $userId = $request->user()?->id ? (int) $request->user()->id : null;

        $this->productEvents->record(
            'template_selected',
            $orgId,
            $userId,
            ['template_id' => $template->id],
            'template_selected:onboarding:org:'.$orgId.':tpl:'.$template->id,
            ['channel' => 'onboarding']
        );
        $this->productEvents->record(
            'template_published',
            $orgId,
            $userId,
            ['template_id' => $template->id, 'source' => 'onboarding'],
            'template_published:tpl:'.$template->id,
            ['channel' => 'onboarding']
        );

        return response()->json([
            'data' => [
                'message' => 'Link público gerado.',
                'public_url' => $this->publicLinkService->getPublicUrl($template),
            ],
        ]);
    }

    /**
     * Envia o link do modelo para o próprio e-mail do usuário (teste sandbox).
     */
    public function enviarParaMim(Request $request, FormTemplate $template): JsonResponse
    {
        $this->authorize('update-template', $template);

        $user = $request->user();
        $email = trim((string) ($user?->email ?? ''));
        if ($email === '') {
            return response()->json(['message' => 'Seu usuário não tem e-mail para receber o teste.'], 422);
        }

        if (! $template->public_enabled || ! $template->public_token) {
            $this->templateVersionService->getOrCreateCurrentVersion($template);
            $this->publicLinkService->generateToken($template);
            $template->refresh();
        }

        $send = $this->documentSendService->sendByEmail(
            $template,
            $email,
            null,
            null,
            null,
            $user?->name ? (string) $user->name : 'Teste Gestgo'
        );

        $orgId = (int) ($template->organization_id ?? $template->clinic_id);
        $this->productEvents->record(
            'first_form_shared',
            $orgId,
            $user?->id ? (int) $user->id : null,
            ['document_send_id' => $send->id, 'source' => 'onboarding_self'],
            'first_form_shared:org:'.$orgId,
            ['channel' => 'onboarding']
        );

        return response()->json([
            'data' => [
                'message' => 'Link de teste enviado para o seu e-mail.',
                'id' => $send->id,
                'public_url' => $this->publicLinkService->getPublicUrl($template),
            ],
        ], 201);
    }
}
