<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiPagination
{
    /** @return array<string, int> */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** @return array<string, string|null> */
    public static function links(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * @param  mixed  $data  Collection ou array serializável
     * @return array<string, mixed>
     */
    public static function wrap(LengthAwarePaginator $paginator, mixed $data): array
    {
        return [
            'data' => $data,
            'meta' => self::meta($paginator),
            'links' => self::links($paginator),
        ];
    }

    public static function perPage(?int $requested, int $default = 20, int $max = 100): int
    {
        if ($requested === null || $requested < 1) {
            return $default;
        }

        return min($requested, $max);
    }
}
