<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    protected $fillable = [
        'key',
        'name',
        'value',
        'value_yearly',
        'value_semiannual',
        'description',
        'max_users',
        'max_organizations_per_tenant',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'value_yearly' => 'decimal:2',
        'value_semiannual' => 'decimal:2',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'max_organizations_per_tenant' => 'integer',
    ];

    /** Ciclos aceitos pelo Asaas neste produto. */
    public const CYCLES = ['MONTHLY', 'SEMIANNUALLY', 'YEARLY'];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget('plans_config');
    }

    /**
     * Valor cobrado no gateway para o ciclo informado.
     * YEARLY: value_yearly se definido, senão 10 × value (mensal).
     * SEMIANNUALLY: value_semiannual se definido, senão 5 × value (mensal).
     *
     * @param  array{value?: float|int|string|null, value_yearly?: float|int|string|null, value_semiannual?: float|int|string|null}  $plan
     */
    public static function resolveChargeValue(array $plan, string $cycle = 'MONTHLY'): float
    {
        $cycle = static::normalizeCycle($cycle);
        $monthly = (float) ($plan['value'] ?? 0);

        if ($cycle === 'YEARLY') {
            $yearly = $plan['value_yearly'] ?? null;
            if ($yearly !== null && $yearly !== '' && (float) $yearly > 0) {
                return round((float) $yearly, 2);
            }

            return round($monthly * 10, 2);
        }

        if ($cycle === 'SEMIANNUALLY') {
            $semi = $plan['value_semiannual'] ?? null;
            if ($semi !== null && $semi !== '' && (float) $semi > 0) {
                return round((float) $semi, 2);
            }

            return round($monthly * 5, 2);
        }

        return round($monthly, 2);
    }

    /**
     * Normaliza ciclo Asaas (MONTHLY|SEMIANNUALLY|YEARLY).
     * Aceita aliases: SEMIANNUAL, SEMESTER, SEMESTRAL.
     */
    public static function normalizeCycle(?string $cycle): string
    {
        $raw = strtoupper(trim((string) $cycle));

        return match ($raw) {
            'YEARLY' => 'YEARLY',
            'SEMIANNUALLY', 'SEMIANNUAL', 'SEMESTER', 'SEMESTRAL' => 'SEMIANNUALLY',
            default => 'MONTHLY',
        };
    }

    /**
     * Retorna planos ativos ordenados para uso em config (formato esperado por asaas.plans).
     */
    public static function getForConfigCached(): array
    {
        return Cache::remember('plans_config', 3600, function () {
            return static::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('key')
                ->get()
                ->keyBy('key')
                ->map(function (Plan $p) {
                    $monthly = (float) $p->value;
                    $yearly = $p->value_yearly !== null
                        ? (float) $p->value_yearly
                        : round($monthly * 10, 2);
                    $semiannual = $p->value_semiannual !== null
                        ? (float) $p->value_semiannual
                        : round($monthly * 5, 2);

                    $row = [
                        'name' => $p->name,
                        'value' => $monthly,
                        'value_yearly' => $yearly,
                        'value_semiannual' => $semiannual,
                        'description' => $p->description,
                    ];
                    if ($p->max_users !== null) {
                        $row['max_users'] = (int) $p->max_users;
                    }
                    if ($p->max_organizations_per_tenant !== null) {
                        $row['max_organizations_per_tenant'] = (int) $p->max_organizations_per_tenant;
                    }

                    return $row;
                })
                ->toArray();
        });
    }

    /**
     * Lista de chaves de planos ativos (para validação in:...).
     */
    public static function activeKeys(): array
    {
        return static::where('is_active', true)->pluck('key')->values()->all();
    }
}
