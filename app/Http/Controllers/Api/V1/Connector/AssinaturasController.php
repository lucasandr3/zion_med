<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssinaturasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::query()
            ->with('organization')
            ->orderByDesc('created_at');

        return ConnectorPaginator::respond(
            $query,
            $request,
            fn (Subscription $subscription) => ConnectorMapper::assinatura($subscription)
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'sub-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Assinatura');
        }

        $subscription = Subscription::query()->with('organization')->find($id);
        if (! $subscription) {
            return ConnectorPaginator::notFound('Assinatura');
        }

        return response()->json(ConnectorMapper::assinatura($subscription));
    }
}
