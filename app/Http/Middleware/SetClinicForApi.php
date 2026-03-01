<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinicForApi
{
    /**
     * Define a clínica do contexto para requisições API (Sanctum).
     * Usa clinic_id do usuário autenticado; opcionalmente X-Clinic-Id para quem pode trocar de clínica.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $clinicId = $user->clinic_id;
        if ($user->canSwitchClinic() && $request->hasHeader('X-Clinic-Id')) {
            $headerClinicId = $request->header('X-Clinic-Id');
            if ($headerClinicId !== null && $headerClinicId !== '') {
                $clinicId = (int) $headerClinicId;
            }
        }

        if ($clinicId !== null) {
            session(['current_clinic_id' => $clinicId]);
        }

        return $next($request);
    }
}
