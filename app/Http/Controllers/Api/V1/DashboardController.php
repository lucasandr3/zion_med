<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Retorna os mesmos dados que a view dashboard (para o front montar a tela).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $orgId = session('current_clinic_id');
        if (! $orgId) {
            return response()->json([
                'data' => [
                    'sem_clinica' => true,
                    'pendentes_hoje' => 0,
                    'ultimos_templates' => [],
                    'por_status' => [],
                    'ultimos_7_dias' => 0,
                    'ultimos_30_dias' => 0,
                    'links_publicos_count' => 0,
                ],
            ]);
        }

        $pendentesHoje = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('status', SubmissionStatus::Pending)
            ->whereDate('created_at', today())
            ->count();

        $porStatus = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v, $k) => (int) $v)
            ->all();

        $ultimos7Dias = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $ultimos30Dias = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $ultimosTemplates = Cache::remember(
            'org:' . $orgId . ':dashboard:last_templates',
            now()->addMinutes(5),
            function () use ($orgId) {
                return FormTemplate::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->latest()
                    ->take(5)
                    ->get();
            }
        );

        $linksPublicosCount = Cache::remember(
            'org:' . $orgId . ':dashboard:public_links_count',
            now()->addMinutes(5),
            function () use ($orgId) {
                return FormTemplate::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->whereNotNull('public_token')
                    ->where('public_enabled', true)
                    ->count();
            }
        );

        return response()->json([
            'data' => [
                'sem_clinica' => false,
                'pendentes_hoje' => $pendentesHoje,
                'ultimos_templates' => TemplateResource::collection($ultimosTemplates),
                'por_status' => $porStatus,
                'ultimos_7_dias' => $ultimos7Dias,
                'ultimos_30_dias' => $ultimos30Dias,
                'links_publicos_count' => $linksPublicosCount,
            ],
        ]);
    }
}
