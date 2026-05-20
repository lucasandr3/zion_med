<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LandingCtaClick;
use App\Services\LandingAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class LandingAnalyticsController extends Controller
{
    public function __construct(private LandingAnalyticsService $analytics) {}

    /**
     * Registra visita única (um IP por dia na landing de marketing).
     * Aceita GET (pixel/script na landing estática) ou POST JSON.
     */
    public function recordView(Request $request): JsonResponse
    {
        if ($this->shouldSkipTracking($request)) {
            return response()->json(['data' => ['recorded' => false, 'skipped' => true]]);
        }

        $path = $request->query('path') ?? $request->input('path');
        if (! is_string($path) || trim($path) === '') {
            return response()->json(['message' => 'O campo path é obrigatório.'], 422);
        }

        $recorded = $this->analytics->recordUniqueVisit($request, $path);

        return response()->json(['data' => ['recorded' => $recorded]]);
    }

    /**
     * Registra clique em CTA da landing (contagem agregada por dia, sem vínculo com Link Bio).
     */
    public function recordCta(Request $request): JsonResponse
    {
        if ($this->shouldSkipTracking($request)) {
            return response()->json(['data' => ['recorded' => false, 'skipped' => true]]);
        }

        $data = $request->validate([
            'channel' => ['required', 'string', 'max:80', Rule::in(LandingAnalyticsService::CTA_CHANNELS)],
        ]);

        LandingCtaClick::incrementFor($data['channel']);

        return response()->json(['data' => ['recorded' => true]]);
    }

    /**
     * Redireciona após registrar clique (opcional para links que preferem passar pela API).
     */
    public function redirectCta(Request $request, string $channel): Response
    {
        if (! in_array($channel, LandingAnalyticsService::CTA_CHANNELS, true)) {
            abort(404);
        }

        $data = $request->validate([
            'to' => ['required', 'url', 'max:2000'],
        ]);

        if (! $this->shouldSkipTracking($request)) {
            LandingCtaClick::incrementFor($channel);
        }

        return redirect()->away($data['to']);
    }

    private function shouldSkipTracking(Request $request): bool
    {
        if ($request->query('preview') === '1') {
            return true;
        }

        $host = strtolower((string) $request->getHost());
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        return false;
    }
}
