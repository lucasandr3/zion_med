<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContatosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', '!=', Role::PlatformAdmin->value)
            ->whereNotNull('organization_id')
            ->orderBy('id');

        return ConnectorPaginator::respond(
            $query,
            $request,
            fn (User $user) => ConnectorMapper::contato($user)
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'user-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Contato');
        }

        $user = User::query()
            ->where('role', '!=', Role::PlatformAdmin->value)
            ->whereNotNull('organization_id')
            ->find($id);

        if (! $user) {
            return ConnectorPaginator::notFound('Contato');
        }

        return response()->json(ConnectorMapper::contato($user));
    }
}
