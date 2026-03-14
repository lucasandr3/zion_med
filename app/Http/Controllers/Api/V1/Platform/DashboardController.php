<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\DemonstrationRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'tenants_count' => Tenant::count(),
                'clinics_count' => Clinic::count(),
                'users_count' => User::count(),
                'leads_count' => DemonstrationRequest::count(),
            ],
        ]);
    }
}
