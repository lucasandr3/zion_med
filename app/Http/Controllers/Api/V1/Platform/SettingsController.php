<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformSettingsRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $asaas = config('asaas', []);

        $data = [
            'product_name' => $asaas['product_name'] ?? PlatformSetting::get('product_name', config('app.name')),
            'trial_days' => (int) ($asaas['trial_days'] ?? PlatformSetting::get('trial_days', 14)),
            'grace_days' => (int) ($asaas['grace_days'] ?? PlatformSetting::get('grace_days', 7)),
            'block_mode' => $asaas['block_mode'] ?? PlatformSetting::get('block_mode', 'soft'),
            'multi_empresa_plan' => $asaas['multi_empresa_plan'] ?? PlatformSetting::get('multi_empresa_plan', 'enterprise'),
            'api_configured' => ! empty($asaas['api_key']),
        ];

        return response()->json(['data' => $data]);
    }

    public function update(PlatformSettingsRequest $request): JsonResponse
    {
        PlatformSetting::set('product_name', $request->input('product_name'));
        PlatformSetting::set('trial_days', $request->input('trial_days'));
        PlatformSetting::set('grace_days', $request->input('grace_days'));
        PlatformSetting::set('block_mode', $request->input('block_mode'));
        PlatformSetting::set('multi_empresa_plan', $request->input('multi_empresa_plan'));

        $asaas = config('asaas', []);

        return response()->json([
            'message' => 'Configurações salvas.',
            'data' => [
                'product_name' => $request->input('product_name'),
                'trial_days' => (int) $request->input('trial_days'),
                'grace_days' => (int) $request->input('grace_days'),
                'block_mode' => $request->input('block_mode'),
                'multi_empresa_plan' => $request->input('multi_empresa_plan'),
                'api_configured' => ! empty($asaas['api_key']),
            ],
        ]);
    }
}
