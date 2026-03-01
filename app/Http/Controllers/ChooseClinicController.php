<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChooseClinicController extends Controller
{
    /**
     * Exibe a tela para escolher a clínica (SuperAdmin ou primeiro acesso).
     */
    public function show(Request $request): View
    {
        $this->authorizeClinicSwitch($request);

        $clinics = Clinic::orderBy('name')->withCount('users')->get();
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

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:clinics,id'],
        ]);

        session(['current_clinic_id' => $validated['clinic_id']]);

        $redirectAfter = $request->input('redirect_after');
        if ($redirectAfter && $this->isSafeInternalRedirect($redirectAfter, $request)) {
            return redirect()->to($redirectAfter)->with('success', 'Clínica alterada.');
        }

        return redirect()->route('dashboard')->with('success', 'Clínica alterada.');
    }

    private function authorizeClinicSwitch(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->canSwitchClinic()) {
            abort(403);
        }
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
