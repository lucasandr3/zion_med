<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Services\LandingAnalyticsService;
use Illuminate\Http\JsonResponse;

class LandingAnalyticsController extends Controller
{
    public function __construct(private LandingAnalyticsService $analytics) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->platformStats(),
        ]);
    }
}
