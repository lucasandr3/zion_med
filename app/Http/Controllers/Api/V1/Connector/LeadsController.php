<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Models\DemonstrationRequest;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DemonstrationRequest::query()->orderByDesc('created_at');

        return ConnectorPaginator::respond(
            $query,
            $request,
            fn (DemonstrationRequest $lead) => ConnectorMapper::lead($lead)
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'lead-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Lead');
        }

        $lead = DemonstrationRequest::query()->find($id);
        if (! $lead) {
            return ConnectorPaginator::notFound('Lead');
        }

        return response()->json(ConnectorMapper::lead($lead));
    }
}
