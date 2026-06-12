<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformStatusRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class PlatformStatusController extends Controller
{
    public function update(PlatformStatusRequest $request): JsonResponse
    {
        PlatformSetting::set('service_status', $request->input('status'));
        PlatformSetting::set('service_status_severity', $request->input('severity'));
        PlatformSetting::set('service_status_message', $request->input('message') ?? '');

        $components = $request->input('components');
        if (is_array($components)) {
            PlatformSetting::set('service_status_components', json_encode($components));
        }

        return response()->json([
            'message' => 'Status atualizado.',
            'data' => PlatformSetting::getServiceStatusPayload(),
        ]);
    }
}
