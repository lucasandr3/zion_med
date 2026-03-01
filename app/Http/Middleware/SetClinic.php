<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinic
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Quem pode trocar de clínica (SuperAdmin ou can_switch_clinic): não sobrescreve a sessão
        if ($user->canSwitchClinic()) {
            $sessionClinic = session('current_clinic_id');

            if ($sessionClinic === null) {
                if ($user->clinic_id !== null) {
                    session(['current_clinic_id' => $user->clinic_id]);
                } elseif (! $request->routeIs('clinica.escolher') && ! $request->routeIs('clinica.escolher.store') && ! $request->routeIs('logout')) {
                    return redirect()->route('clinica.escolher');
                }
            }
            return $next($request);
        }

        if ($user->clinic_id === null) {
            session()->forget('current_clinic_id');
            return $next($request);
        }

        if (session('current_clinic_id') !== (string) $user->clinic_id) {
            session(['current_clinic_id' => $user->clinic_id]);
        }

        return $next($request);
    }
}
