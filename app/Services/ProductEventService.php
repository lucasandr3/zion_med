<?php

namespace App\Services;

use App\Models\ProductEvent;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductEventService
{
    /** @var list<string> */
    public const FUNNEL_EVENTS = [
        'signup_started',
        'signup_completed',
        'email_verified',
        'trial_started',
        'onboarding_segment_selected',
        'template_selected',
        'template_import_started',
        'template_created',
        'template_published',
        'first_form_shared',
        'first_protocol_completed',
        'checkout_started',
        'checkout_failed',
        'payment_confirmed',
        'subscription_cancelled',
    ];

    /**
     * Persiste evento de produto sem PII clínica. Idempotente por chave.
     *
     * @param  array<string, mixed>  $properties
     * @param  array{channel?: ?string, utm_source?: ?string, utm_medium?: ?string, utm_campaign?: ?string}  $attribution
     */
    public function record(
        string $eventName,
        ?int $organizationId = null,
        ?int $userId = null,
        array $properties = [],
        ?string $idempotencyKey = null,
        array $attribution = [],
    ): ?ProductEvent {
        $eventName = Str::lower(trim($eventName));
        if ($eventName === '' || strlen($eventName) > 80) {
            return null;
        }

        $safeProps = $this->sanitizeProperties($properties);
        $key = $idempotencyKey !== null && $idempotencyKey !== ''
            ? mb_substr($idempotencyKey, 0, 191)
            : hash('sha256', implode('|', [
                $eventName,
                (string) ($organizationId ?? 0),
                (string) ($userId ?? 0),
                json_encode($safeProps, JSON_UNESCAPED_UNICODE) ?: '',
                now()->format('Y-m-d-H-i'),
            ]));

        try {
            $existing = ProductEvent::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            return ProductEvent::query()->create([
                'event_name' => $eventName,
                'idempotency_key' => $key,
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'channel' => $this->clip($attribution['channel'] ?? null, 80),
                'utm_source' => $this->clip($attribution['utm_source'] ?? null, 120),
                'utm_medium' => $this->clip($attribution['utm_medium'] ?? null, 120),
                'utm_campaign' => $this->clip($attribution['utm_campaign'] ?? null, 160),
                'properties' => $safeProps === [] ? null : $safeProps,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Unique race: treat as success
            $existing = ProductEvent::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }
            Log::warning('ProductEvent record failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Funil agregado para admin da plataforma (últimos N dias).
     *
     * @return array{days: int, totals: array<string, int>, by_day: list<array{date: string, counts: array<string, int>}>}
     */
    public function funnelSummary(int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = ProductEvent::query()
            ->where('occurred_at', '>=', $from)
            ->whereIn('event_name', self::FUNNEL_EVENTS)
            ->selectRaw('event_name, DATE(occurred_at) as d, COUNT(*) as c')
            ->groupBy('event_name', 'd')
            ->orderBy('d')
            ->get();

        $totals = array_fill_keys(self::FUNNEL_EVENTS, 0);
        $byDayMap = [];
        foreach ($rows as $row) {
            $name = (string) $row->event_name;
            $date = (string) $row->d;
            $count = (int) $row->c;
            if (! isset($totals[$name])) {
                $totals[$name] = 0;
            }
            $totals[$name] += $count;
            if (! isset($byDayMap[$date])) {
                $byDayMap[$date] = array_fill_keys(self::FUNNEL_EVENTS, 0);
            }
            $byDayMap[$date][$name] = $count;
        }

        $byDay = [];
        foreach ($byDayMap as $date => $counts) {
            $byDay[] = ['date' => $date, 'counts' => $counts];
        }

        return [
            'days' => $days,
            'totals' => $totals,
            'by_day' => $byDay,
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function sanitizeProperties(array $properties): array
    {
        $blocked = [
            'cpf', 'cnpj', 'password', 'token', 'email', 'phone', 'name',
            'responsible_name', 'company_name', 'answers', 'values', 'form_data',
            'patient', 'documento', 'document',
        ];
        $out = [];
        foreach ($properties as $k => $v) {
            $key = Str::lower((string) $k);
            if (in_array($key, $blocked, true)) {
                continue;
            }
            if (is_scalar($v) || $v === null) {
                $out[$key] = is_string($v) ? mb_substr($v, 0, 200) : $v;
            }
        }

        return $out;
    }

    private function clip(mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : mb_substr($s, 0, $max);
    }

    /**
     * Organizações recentes e última etapa do funil alcançada (sem PII).
     *
     * @return list<array{organization_id: int, last_event: string, occurred_at: string, niche: ?string}>
     */
    public function abandonmentSnapshot(int $days = 30, int $limit = 40): array
    {
        $days = max(1, min(90, $days));
        $from = now()->subDays($days)->startOfDay();
        $priority = array_flip(self::FUNNEL_EVENTS);

        $rows = ProductEvent::query()
            ->whereNotNull('organization_id')
            ->where('occurred_at', '>=', $from)
            ->whereIn('event_name', self::FUNNEL_EVENTS)
            ->orderByDesc('occurred_at')
            ->limit(2000)
            ->get(['organization_id', 'event_name', 'occurred_at']);

        $best = [];
        foreach ($rows as $row) {
            $orgId = (int) $row->organization_id;
            $name = (string) $row->event_name;
            $rank = $priority[$name] ?? -1;
            if (! isset($best[$orgId]) || $rank > ($priority[$best[$orgId]['last_event']] ?? -1)) {
                $best[$orgId] = [
                    'organization_id' => $orgId,
                    'last_event' => $name,
                    'occurred_at' => $row->occurred_at?->toIso8601String() ?? '',
                ];
            }
        }

        $orgIds = array_keys($best);
        $niches = Organization::query()
            ->whereIn('id', $orgIds)
            ->pluck('niche', 'id');

        $out = [];
        foreach ($best as $orgId => $row) {
            $row['niche'] = $niches[$orgId] ?? null;
            $out[] = $row;
        }

        usort($out, fn ($a, $b) => strcmp($b['occurred_at'], $a['occurred_at']));

        return array_slice($out, 0, max(1, min(100, $limit)));
    }
}
