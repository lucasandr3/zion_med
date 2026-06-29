<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormTemplate;
use App\Services\PublicLinkService;
use App\Services\TemplateVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly TemplateVersionService $templateVersionService,
        private readonly PublicLinkService $publicLinkService,
    ) {}

    /**
     * Lista modelos da organização para o wizard pós-cadastro (sem exigir e-mail verificado).
     */
    public function templates(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');

        $items = FormTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(12)
            ->get();

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

        return response()->json([
            'data' => [
                'message' => 'Link público gerado.',
                'public_url' => $this->publicLinkService->getPublicUrl($template),
            ],
        ]);
    }
}
