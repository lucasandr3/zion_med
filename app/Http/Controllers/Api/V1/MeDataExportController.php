<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AuditLog;
use App\Models\FormSubmission;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class MeDataExportController extends Controller
{
    /**
     * Exportação dos dados pessoais do titular (LGPD — portabilidade / acesso).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = session('current_organization_id') ?? session('current_clinic_id') ?? $user->organization_id;
        $organization = $organizationId ? Organization::query()->find((int) $organizationId) : null;

        $auditLogs = AuditLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get(['id', 'action', 'entity_type', 'entity_id', 'meta_json', 'organization_id', 'created_at']);

        $protocolFields = ['id', 'protocol_number', 'status', 'template_id', 'organization_id', 'submitted_at', 'approved_at'];

        $submitted = FormSubmission::withoutGlobalScopes()
            ->where('submitted_by_user_id', $user->id)
            ->with('template:id,name')
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get($protocolFields);

        $approved = FormSubmission::withoutGlobalScopes()
            ->where('approved_by_user_id', $user->id)
            ->with('template:id,name')
            ->orderByDesc('approved_at')
            ->limit(200)
            ->get($protocolFields);

        Event::dispatch(new AuditEvent(
            'user.data_exported',
            User::class,
            $user->id,
            null,
            $organizationId ? (int) $organizationId : null,
            $user->id
        ));

        return response()->json([
            'data' => [
                'exported_at' => now()->toIso8601String(),
                'format_version' => '1.0',
                'notice' => 'Este arquivo contém dados da sua conta de usuário no Gestgo. Dados de pacientes e protocolos completos são de responsabilidade da clínica (controladora); aqui constam apenas metadados das ações vinculadas ao seu usuário.',
                'account' => (new UserResource($user))->resolve(),
                'current_organization' => $organization ? [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'contact_email' => $organization->contact_email,
                ] : null,
                'audit_logs' => $auditLogs->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'meta_json' => $log->meta_json,
                    'organization_id' => $log->organization_id,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->values()->all(),
                'protocols_activity' => [
                    'submitted' => $submitted->map(fn (FormSubmission $s) => $this->protocolSummary($s))->values()->all(),
                    'approved' => $approved->map(fn (FormSubmission $s) => $this->protocolSummary($s))->values()->all(),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function protocolSummary(FormSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'protocol_number' => $submission->protocol_number,
            'status' => $submission->status instanceof \BackedEnum ? $submission->status->value : (string) $submission->status,
            'template_id' => $submission->template_id,
            'template_name' => $submission->relationLoaded('template') ? $submission->template?->name : null,
            'organization_id' => $submission->organization_id,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'approved_at' => $submission->approved_at?->toIso8601String(),
        ];
    }
}
