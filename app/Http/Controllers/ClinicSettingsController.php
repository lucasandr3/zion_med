<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicSettingsRequest;
use App\Models\Clinic;
use App\Services\AuditService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicSettingsController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ThemeService $themeService,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-clinic');

        $clinic = Clinic::findOrFail(session('current_clinic_id'));

        return view('clinica.configuracoes', [
            'clinic'          => $clinic,
            'availableThemes' => $this->themeService->getAvailableThemes(),
        ]);
    }

    public function update(ClinicSettingsRequest $request): RedirectResponse
    {
        $clinic = Clinic::findOrFail(session('current_clinic_id'));
        $this->authorize('update-clinic', $clinic);
        $data = $request->validated();

        // Dark-mode-only AJAX toggle (sent from the layout toggle button)
        if ($request->boolean('dark_mode_only')) {
            $clinic->update(['dark_mode' => $request->boolean('dark_mode')]);
            return back();
        }

        // Theme-only AJAX change (sent from the header theme picker)
        if ($request->boolean('theme_only')) {
            $clinic->update(['theme' => $request->input('theme')]);
            return back();
        }

        if ($request->hasFile('logo')) {
            if ($clinic->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);

        if ($request->hasFile('cover_image')) {
            if ($clinic->cover_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clinic->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($data['cover_image']);

        if (isset($data['business_hours'])) {
            $cleaned = [];
            foreach ($data['business_hours'] as $d => $slot) {
                $open  = trim($slot['open'] ?? '');
                $close = trim($slot['close'] ?? '');
                if ($open !== '' && $close !== '') {
                    $cleaned[$d] = ['open' => $open, 'close' => $close];
                }
            }
            $data['business_hours'] = ! empty($cleaned) ? $cleaned : null;
        }

        foreach (['phone', 'contact_email', 'short_description', 'specialties', 'founded_year', 'meta_description', 'maps_url'] as $key) {
            if (isset($data[$key]) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        $clinic->update($data);
        $this->auditService->log('clinic.updated', Clinic::class, $clinic->id);

        return redirect()->route('clinica.configuracoes.edit')
            ->with('success', 'Configurações salvas com sucesso.');
    }
}
