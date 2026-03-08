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
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
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
                ->map(fn (Plan $p) => [
                    'name' => $p->name,
                    'value' => (float) $p->value,
                    'description' => $p->description,
                ])
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
