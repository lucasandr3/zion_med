<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;

class PermissionCatalogController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $this->authorize('manage-users');

        return response()->json([
            'data' => Permission::catalog(),
        ]);
    }
}
