<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TemplateResource;
use App\Models\DocumentSend;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Organization;
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
        $this->authorize('view-dashboard');

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
                    'media_semanal_ultimos_30_dias' => 0,
                    'comparativo_semana_anterior' => [
                        'delta_absoluto' => 0,
                        'delta_percentual' => 0,
                        'positiva' => true,
                    ],
                    'taxa_aprovacao' => 0,
                    'links_publicos_count' => 0,
                    'ultimas_submissoes' => [],
                    'modelos_mais_usados' => [],
                    'respostas_por_template' => [],
                    'onboarding' => [
                        'needs_public_link' => false,
                        'public_links_count' => 0,
                    ],
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

        $semanaAnterior = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        $mediaSemanalUltimos30Dias = (int) round($ultimos30Dias / (30 / 7));
        $deltaAbsoluto = $ultimos7Dias - $semanaAnterior;
        $deltaPercentual = $semanaAnterior > 0
            ? (int) round(($deltaAbsoluto / $semanaAnterior) * 100)
            : ($ultimos7Dias > 0 ? 100 : 0);

        $ultimosTemplates = Cache::remember(
            'org:' . $orgId . ':dashboard:last_templates',
            now()->addMinutes(5),
            function () use ($orgId) {
                $niche = (string) (Organization::query()->whereKey($orgId)->value('niche') ?: 'estetica');

                return FormTemplate::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->visibleForNiche($niche)
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

        $documentSendsPendentes = DocumentSend::where('organization_id', $orgId)
            ->whereNull('form_submission_id')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        $documentSendsExpirados = DocumentSend::where('organization_id', $orgId)
            ->whereNull('form_submission_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        $totalStatus = array_sum($porStatus);
        $aprovados = (int) ($porStatus[SubmissionStatus::Approved->value] ?? 0);
        $taxaAprovacao = $totalStatus > 0 ? (int) round(($aprovados / $totalStatus) * 100) : 0;

        $ultimasSubmissoes = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->with(['template:id,name', 'person:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (FormSubmission $submission): array {
                return [
                    'id' => (int) $submission->id,
                    'paciente' => $submission->person?->name ?: 'Anônimo',
                    'modelo' => $submission->template?->name ?: 'Modelo removido',
                    'status' => $submission->status?->value ?? (string) $submission->status,
                    'data' => optional($submission->created_at)->toISOString(),
                ];
            })
            ->values()
            ->all();

        $modelosMaisUsados = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->whereNotNull('template_id')
            ->selectRaw('template_id, count(*) as total')
            ->groupBy('template_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function ($row) {
                $template = FormTemplate::withoutGlobalScopes()->find($row->template_id);
                return [
                    'template_id' => (int) $row->template_id,
                    'template_nome' => $template?->name ?? 'Modelo removido',
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();

        $respostasPorTemplate = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->whereNotNull('template_id')
            ->selectRaw('template_id, count(*) as total')
            ->groupBy('template_id')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(function ($row) {
                return [(int) $row->template_id => (int) $row->total];
            })
            ->all();

        return response()->json([
            'data' => [
                'sem_clinica' => false,
                'pendentes_hoje' => $pendentesHoje,
                'ultimos_templates' => TemplateResource::collection($ultimosTemplates),
                'por_status' => $porStatus,
                'ultimos_7_dias' => $ultimos7Dias,
                'ultimos_30_dias' => $ultimos30Dias,
                'media_semanal_ultimos_30_dias' => $mediaSemanalUltimos30Dias,
                'comparativo_semana_anterior' => [
                    'delta_absoluto' => $deltaAbsoluto,
                    'delta_percentual' => $deltaPercentual,
                    'positiva' => $deltaAbsoluto >= 0,
                ],
                'taxa_aprovacao' => $taxaAprovacao,
                'links_publicos_count' => $linksPublicosCount,
                'documentos_pendentes_assinatura' => $documentSendsPendentes,
                'documentos_expirados' => $documentSendsExpirados,
                'ultimas_submissoes' => $ultimasSubmissoes,
                'modelos_mais_usados' => $modelosMaisUsados,
                'respostas_por_template' => $respostasPorTemplate,
                'onboarding' => [
                    'needs_public_link' => $linksPublicosCount === 0,
                    'public_links_count' => $linksPublicosCount,
                ],
            ],
        ]);
    }
}
