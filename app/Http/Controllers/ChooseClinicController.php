<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChooseClinicController extends Controller
{
    /**
     * Exibe a tela para escolher a clínica (dentro do mesmo tenant).
     */
    public function show(Request $request): View
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $clinics = $this->clinicsAllowedForUser($user);
        $currentClinicId = session('current_clinic_id');

        return view('clinica.escolher', [
            'clinics' => $clinics,
            'currentClinicId' => $currentClinicId,
        ]);
    }

    /**
     * Define a clínica atual na sessão e redireciona (login ou troca no sistema).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $allowedClinicIds = $this->clinicsAllowedForUser($user)->pluck('id')->all();

        if (empty($allowedClinicIds)) {
            abort(403, 'Nenhuma empresa disponível para seleção.');
        }

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'in:' . implode(',', $allowedClinicIds)],
        ]);

        session(['current_clinic_id' => $validated['clinic_id']]);

        $redirectAfter = $request->input('redirect_after');
        if ($redirectAfter && $this->isSafeInternalRedirect($redirectAfter, $request)) {
            return redirect()->to($redirectAfter)->with('success', 'Empresa alterada.');
        }

        return redirect()->route('dashboard')->with('success', 'Empresa alterada.');
    }

    /**
     * Permite acesso se: SuperAdmin/can_switch_clinic OU se o tenant do usuário tem mais de uma clínica
     * (ex.: plano Enterprise com várias empresas no grupo).
     */
    private function authorizeClinicSwitch(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($user->canSwitchClinic()) {
            return;
        }
        $clinic = $user->clinic;
        if ($clinic?->tenant_id && Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->count() > 1) {
            return;
        }
        abort(403);
    }

    /**
     * Clínicas que o usuário pode escolher: sempre dentro do mesmo tenant.
     */
    private function clinicsAllowedForUser($user): \Illuminate\Database\Eloquent\Collection
    {
        $tenantId = $user->clinic?->tenant_id;
        if ($tenantId === null) {
            return $user->clinic_id
                ? Clinic::where('id', $user->clinic_id)->withCount('users')->get()
                : Clinic::whereRaw('0 = 1')->withCount('users')->get();
        }

        return Clinic::where('tenant_id', $tenantId)->orderBy('name')->withCount('users')->get();
    }

    /** Só redireciona para URL interna (relativa ou mesmo host), evitando voltar para login ou para a própria tela de escolher. */
    private function isSafeInternalRedirect(string $url, Request $request): bool
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? $url;

        if (isset($parsed['host']) && $parsed['host'] !== '' && $parsed['host'] !== $request->getHost()) {
            return false;
        }
        if (str_contains($path, '/login') || str_contains($path, '/clinica/escolher')) {
            return false;
        }

        return true;
    }
}
