<?php

namespace App\Support\Connector;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ConnectorPaginator
{
    /**
     * @param  callable(mixed): array<string, mixed>  $mapper
     */
    public static function respond(Builder $query, Request $request, callable $mapper): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $pageSize = min(500, max(1, (int) $request->query('pageSize', 100)));

        self::applyUpdatedSince($query, $request->query('updated_since'));

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map($mapper)->values(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ]);
    }

    public static function applyUpdatedSince(Builder $query, mixed $updatedSince): void
    {
        if ($updatedSince === null || $updatedSince === '') {
            return;
        }

        try {
            $since = Carbon::parse((string) $updatedSince);
        } catch (\Throwable) {
            return;
        }

        $query->where('updated_at', '>=', $since);
    }

    public static function notFound(string $resource): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => "{$resource} não encontrado.",
            ],
        ], 404);
    }

    public static function parseExternalId(string $externalId, string $prefix): ?int
    {
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';
        if (! preg_match($pattern, $externalId, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
