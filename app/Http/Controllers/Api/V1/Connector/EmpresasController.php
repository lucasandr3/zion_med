<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()->orderBy('id');

        return ConnectorPaginator::respond(
            $query,
            $request,
            fn (Organization $organization) => ConnectorMapper::empresa($organization)
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'org-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Empresa');
        }

        $organization = Organization::query()->find($id);
        if (! $organization) {
            return ConnectorPaginator::notFound('Empresa');
        }

        return response()->json(ConnectorMapper::empresa($organization));
    }
}
