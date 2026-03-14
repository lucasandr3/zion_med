<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformStatusRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PlatformStatusController extends Controller
{
    public function update(PlatformStatusRequest $request): RedirectResponse|JsonResponse
    {
        PlatformSetting::set('service_status', $request->input('status'));
        PlatformSetting::set('service_status_severity', $request->input('severity'));
        PlatformSetting::set('service_status_message', $request->input('message') ?? '');

        $components = $request->input('components', []);
        PlatformSetting::set('service_status_components', json_encode($components));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Status atualizado.',
                'data' => PlatformSetting::getServiceStatusPayload(),
            ]);
        }

        return redirect()->back()->with('success_status', 'Status atualizado.');
    }
}
