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

        $target = $this->assertAllowedRedirectTarget((string) $data['to']);

        if (! $this->shouldSkipTracking($request)) {
            LandingCtaClick::incrementFor($channel);
        }

        return redirect()->away($target);
    }

    /**
     * Evita open redirect: só HTTPS (ou HTTP em local) para hosts allowlistados.
     */
    private function assertAllowedRedirectTarget(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            abort(422, 'URL de redirecionamento inválida.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $allowedSchemes = app()->environment('production') ? ['https'] : ['https', 'http'];
        if (! in_array($scheme, $allowedSchemes, true)) {
            abort(422, 'Esquema de URL não permitido.');
        }

        $allowed = $this->allowedRedirectHosts();
        $ok = false;
        foreach ($allowed as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            abort(422, 'Destino de redirecionamento não permitido.');
        }

        return $url;
    }

    /**
     * @return list<string>
     */
    private function allowedRedirectHosts(): array
    {
        $hosts = [
            'gestgo.com.br',
            'app.gestgo.com.br',
            'site-homolog.gestgo.com.br',
            'homolog.gestgo.com.br',
            'wa.me',
            'api.whatsapp.com',
            'www.instagram.com',
            'instagram.com',
            'maps.google.com',
            'www.google.com',
            'goo.gl',
            'maps.app.goo.gl',
        ];

        $frontend = (string) config('app.frontend_url', '');
        if ($frontend !== '') {
            $fh = parse_url($frontend, PHP_URL_HOST);
            if (is_string($fh) && $fh !== '') {
                $hosts[] = strtolower($fh);
            }
        }

        $extra = array_filter(array_map('trim', explode(',', (string) env('LANDING_REDIRECT_ALLOWED_HOSTS', ''))));
        foreach ($extra as $h) {
            $hosts[] = strtolower($h);
        }

        return array_values(array_unique($hosts));
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
