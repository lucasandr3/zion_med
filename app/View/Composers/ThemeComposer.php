<?php

namespace App\View\Composers;

use App\Models\Clinic;
use App\Services\ThemeService;
use Illuminate\View\View;

class ThemeComposer
{
    public function __construct(private ThemeService $themeService) {}

    public function compose(View $view): void
    {
        $clinicId = session('current_clinic_id');
        $clinic   = $clinicId ? Clinic::find($clinicId) : null;

        $user = request()->user();
        $canSwitchClinic = $user?->canSwitchClinic() ?? false;
        $hasMultipleClinicsInTenant = $clinic?->tenant_id
            && Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->count() > 1;
        $showTrocarEmpresa = $canSwitchClinic || $hasMultipleClinicsInTenant;

        $view->with([
            'themeBodyClasses'      => $this->themeService->getBodyClasses($clinic),
            'availableThemes'       => $this->themeService->getAvailableThemes(),
            'currentTheme'          => $this->themeService->getClinicTheme($clinic),
            'isDarkMode'            => (bool) ($clinic?->dark_mode ?? false),
            'currentClinic'         => $clinic,
            'canSwitchClinic'       => $canSwitchClinic,
            'showTrocarEmpresa'     => $showTrocarEmpresa,
            'unreadNotifications'   => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
