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
        'description',
        'max_users',
        'max_organizations_per_tenant',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'max_organizations_per_tenant' => 'integer',
    ];

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
                    $row = [
                        'name' => $p->name,
                        'value' => (float) $p->value,
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
