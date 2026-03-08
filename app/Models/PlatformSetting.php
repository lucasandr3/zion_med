<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget('platform_settings');
    }

    /**
     * Retorna todas as configurações como array associativo key => value.
     */
    public static function getAllCached(): array
    {
        return Cache::remember('platform_settings', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Retorna o valor de uma chave ou o default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::getAllCached();

        return $all[$key] ?? $default;
    }

    /**
     * Define o valor de uma chave.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value)]
        );
    }
}
