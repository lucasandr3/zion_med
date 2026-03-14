<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\DemonstrationRequest;
use Illuminate\Http\JsonResponse;

class LeadsController extends Controller
{
    public function index(): JsonResponse
    {
        $requests = DemonstrationRequest::orderByDesc('created_at')->get();

        return response()->json([
            'data' => $requests->map(fn (DemonstrationRequest $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'clinic' => $r->clinic,
                'email' => $r->email,
                'phone' => $r->phone,
                'message' => $r->message,
                'created_at' => $r->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
