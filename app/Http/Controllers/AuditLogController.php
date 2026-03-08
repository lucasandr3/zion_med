<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Lista os logs de auditoria da empresa atual (clínica).
     * Apenas quem pode gerenciar a clínica (Owner/Manager) vê esta tela.
     */
    public function index(Request $request): View
    {
        $this->authorize('manage-clinic');

        $organizationId = session('current_clinic_id');
        if (! $organizationId) {
            abort(403, 'Nenhuma empresa selecionada.');
        }

        $logs = AuditLog::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->paginate(50);

        $clinic = Clinic::find($organizationId);

        return view('clinica.logs.index', [
            'logs' => $logs,
            'clinic' => $clinic,
        ]);
    }

    /**
     * Lista os logs de auditoria do dono da plataforma (apenas ações dele).
     * Acesso apenas para usuários da plataforma (middleware platform).
     */
    public function platformIndex(Request $request): View
    {
        $logs = AuditLog::query()
            ->with(['user', 'organization'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('platform.logs.index', [
            'logs' => $logs,
        ]);
    }
}
