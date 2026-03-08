<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformSettingsRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    /**
     * Exibe as configurações da plataforma. Valores editáveis vêm do banco; API/URL do .env.
     */
    public function index(): View
    {
        $asaas = config('asaas', []);

        return view('platform.settings.index', [
            'productName' => $asaas['product_name'] ?? config('app.name'),
            'baseUrl' => $asaas['base_url'] ?? null,
            'trialDays' => $asaas['trial_days'] ?? null,
            'graceDays' => $asaas['grace_days'] ?? null,
            'blockMode' => $asaas['block_mode'] ?? null,
            'multiEmpresaPlan' => $asaas['multi_empresa_plan'] ?? null,
            'apiConfigured' => ! empty($asaas['api_key']),
        ]);
    }

    public function update(PlatformSettingsRequest $request): RedirectResponse
    {
        PlatformSetting::set('product_name', $request->input('product_name'));
        PlatformSetting::set('trial_days', $request->input('trial_days'));
        PlatformSetting::set('grace_days', $request->input('grace_days'));
        PlatformSetting::set('block_mode', $request->input('block_mode'));
        PlatformSetting::set('multi_empresa_plan', $request->input('multi_empresa_plan'));

        return redirect()->route('platform.settings.index')->with('success', 'Configurações salvas.');
    }
}
