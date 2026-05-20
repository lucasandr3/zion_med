<?php

namespace App\Services;

use App\Models\LandingCtaClick;
use App\Models\LandingSiteVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingAnalyticsService
{
    /** Canais de CTA conhecidos na landing (gestgo-landing). */
    public const CTA_CHANNELS = [
        'hero_cta',
        'nav_cta',
        'comece',
        'demo',
        'pricing',
        'blog',
    ];

    public function hashIp(Request $request): string
    {
        $ip = (string) $request->ip();

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    public function normalizePath(?string $path): string
    {
        $raw = trim((string) $path);
        if ($raw === '') {
            return '/';
        }
        $parsed = parse_url($raw, PHP_URL_PATH);
        $normalized = is_string($parsed) && $parsed !== '' ? $parsed : $raw;
        $normalized = '/' . ltrim($normalized, '/');
        if (strlen($normalized) > 500) {
            $normalized = substr($normalized, 0, 500);
        }

        return $normalized === '' ? '/' : $normalized;
    }

    /**
     * Registra visita única: um IP por dia (não incrementa se o IP já visitou hoje).
     */
    public function recordUniqueVisit(Request $request, string $path): bool
    {
        $visitDate = now()->toDateString();
        $ipHash = $this->hashIp($request);
        $path = $this->normalizePath($path);

        $existing = LandingSiteVisit::query()
            ->where('ip_hash', $ipHash)
            ->where('visit_date', $visitDate)
            ->first();

        if ($existing) {
            return false;
        }

        LandingSiteVisit::query()->create([
            'ip_hash'    => $ipHash,
            'visit_date' => $visitDate,
            'path'       => $path,
        ]);

        return true;
    }

    public function ctaLabel(string $channel): string
    {
        return match ($channel) {
            'hero_cta' => 'CTA principal (hero)',
            'nav_cta'  => 'CTA do menu',
            'comece'   => 'Começar / cadastro',
            'demo'     => 'Demonstração',
            'pricing'  => 'Planos / preços',
            'blog'     => 'Blog',
            default    => $channel,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function platformStats(): array
    {
        $today = Carbon::today()->toDateString();
        $cut30 = Carbon::today()->subDays(30)->toDateString();

        $visitasHoje = (int) LandingSiteVisit::query()
            ->where('visit_date', $today)
            ->distinct('ip_hash')
            ->count('ip_hash');

        $totalUniqueVisitors = (int) LandingSiteVisit::query()
            ->distinct('ip_hash')
            ->count('ip_hash');

        $uniqueLast30 = (int) LandingSiteVisit::query()
            ->where('visit_date', '>=', $cut30)
            ->distinct('ip_hash')
            ->count('ip_hash');

        $totalClicks = (int) LandingCtaClick::query()->sum('clicks');
        $totalClicksLast30 = (int) LandingCtaClick::query()
            ->where('date', '>=', $cut30)
            ->sum('clicks');

        $taxaClique = $uniqueLast30 > 0
            ? round($totalClicksLast30 / $uniqueLast30 * 100, 1)
            : 0;

        $viewsPerDay = [];
        $clicksPerDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $viewsPerDay[$date] = (int) LandingSiteVisit::query()
                ->where('visit_date', $date)
                ->distinct('ip_hash')
                ->count('ip_hash');
            $clicksPerDay[$date] = (int) LandingCtaClick::query()
                ->where('date', $date)
                ->sum('clicks');
        }

        $pathBreakdown = LandingSiteVisit::query()
            ->select('path', DB::raw('COUNT(DISTINCT ip_hash) as unique_visitors'))
            ->where('visit_date', '>=', $cut30)
            ->groupBy('path')
            ->orderByDesc('unique_visitors')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'path'             => (string) $row->path,
                'unique_visitors'  => (int) $row->unique_visitors,
            ])
            ->values()
            ->all();

        $clickBreakdown = [];
        $ctaGrouped = LandingCtaClick::query()
            ->selectRaw('channel, SUM(clicks) as total')
            ->groupBy('channel')
            ->get();

        foreach ($ctaGrouped as $row) {
            $channel = (string) $row->channel;
            $totalCtaRow = (int) $row->total;
            $last30Cta = (int) LandingCtaClick::query()
                ->where('channel', $channel)
                ->where('date', '>=', $cut30)
                ->sum('clicks');
            if ($totalCtaRow === 0 && $last30Cta === 0) {
                continue;
            }
            $clickBreakdown[] = [
                'kind'           => 'cta',
                'channel'        => $channel,
                'label'          => $this->ctaLabel($channel),
                'total_clicks'   => $totalCtaRow,
                'total_last_30'  => $last30Cta,
            ];
        }

        usort($clickBreakdown, fn (array $a, array $b): int => $b['total_last_30'] <=> $a['total_last_30']
            ?: strcmp((string) $a['label'], (string) $b['label']));

        $peakDayLabel = null;
        $dayWithMost = LandingSiteVisit::query()
            ->select('visit_date', DB::raw('COUNT(DISTINCT ip_hash) as visitors'))
            ->groupBy('visit_date')
            ->orderByDesc('visitors')
            ->first();
        if ($dayWithMost && (int) $dayWithMost->visitors > 0) {
            $peakDayLabel = ucfirst(Carbon::parse($dayWithMost->visit_date)->locale('pt_BR')->dayName);
        }

        return [
            'visitas_hoje'           => $visitasHoje,
            'total_views'            => $totalUniqueVisitors,
            'unique_visitors_last_30'=> $uniqueLast30,
            'total_clicks'           => $totalClicks,
            'total_clicks_last_30'   => $totalClicksLast30,
            'taxa_clique'            => $taxaClique,
            'clicks_per_day'         => $clicksPerDay,
            'views_per_day'          => $viewsPerDay,
            'path_breakdown'         => $pathBreakdown,
            'peak_day_label'         => $peakDayLabel,
            'click_breakdown'        => $clickBreakdown,
        ];
    }
}
