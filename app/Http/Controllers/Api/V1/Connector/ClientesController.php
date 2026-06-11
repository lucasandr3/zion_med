<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Tenant;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()->orderBy('id');

        return ConnectorPaginator::respond(
            $query,
            $request,
            function (Tenant $tenant): array {
                $primaryOrganization = Organization::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->first();

                return ConnectorMapper::cliente($tenant, $primaryOrganization);
            }
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'tenant-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Cliente');
        }

        $tenant = Tenant::query()->find($id);
        if (! $tenant) {
            return ConnectorPaginator::notFound('Cliente');
        }

        $primaryOrganization = Organization::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        return response()->json(ConnectorMapper::cliente($tenant, $primaryOrganization));
    }
}
