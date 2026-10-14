<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Services\ProductEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductFunnelController extends Controller
{
    public function __construct(private ProductEventService $events) {}

    public function __invoke(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);

        $summary = $this->events->funnelSummary($days);
        $summary['abandonment'] = $this->events->abandonmentSnapshot($days);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
