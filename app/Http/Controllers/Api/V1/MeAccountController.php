<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationBillingCancellationService;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MeAccountController extends Controller
{
    public function __construct(
        private OrganizationBillingCancellationService $billingCancellation,
    ) {}

    /**
     * Exclusão da conta pelo próprio titular (LGPD — eliminação / revogação de acesso).
     *
     * Corpo JSON: `{ "password": "..." }`.
     *
     * Se o usuário for o último com permissão de assinatura/cobrança na empresa, cancela assinaturas ativas no Asaas.
     * Se for o único usuário ativo e puder gerenciar cobrança, permite exclusão e cancela a assinatura.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Senha incorreta.'],
            ]);
        }

        if ($user->isPlatformAdmin()) {
            return response()->json([
                'message' => 'Contas de administrador da plataforma não podem ser excluídas por aqui. Entre em contato com o suporte.',
            ], 422);
        }

        $orgId = $user->organization_id ? (int) $user->organization_id : null;
        $organization = $orgId ? Organization::query()->find($orgId) : null;

        $activePeers = 0;
        if ($orgId) {
            $activePeers = User::withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->where('active', true)
                ->where('id', '!=', $user->id)
                ->count();
        }

        $managesBilling = $user->hasPermission(Permission::BILLING_MANAGE);
        $isLastBillingManager = $managesBilling && $orgId && $this->countOtherActiveBillingManagers($orgId, $user->id) === 0;

        if ($activePeers === 0 && ! $managesBilling) {
            return response()->json([
                'message' => 'Você é o único usuário ativo desta empresa. Cadastre outro usuário com permissão de assinatura ou transfira a gestão antes de excluir sua conta.',
            ], 422);
        }

        $billingCanceled = false;
        if ($organization && $isLastBillingManager) {
            $this->billingCancellation->cancelActiveGatewaySubscriptions($organization);
            $organization->refresh();
            $billingCanceled = true;
        }

        $this->deleteElectronicSignature($user);

        $user->tokens()->delete();

        $originalEmail = (string) $user->email;
        $user->forceFill([
            'name' => 'Conta excluída',
            'email' => 'deleted_' . $user->id . '_' . Str::lower(Str::random(8)) . '@deleted.gestgo.local',
            'password' => Hash::make(Str::random(64)),
            'active' => false,
            'electronic_signature_path' => null,
            'electronic_signature_updated_at' => null,
            'remember_token' => null,
            'email_verified_at' => null,
        ])->save();

        Event::dispatch(new AuditEvent(
            'user.self_deleted',
            User::class,
            $user->id,
            [
                'email_hash' => hash('sha256', Str::lower($originalEmail)),
                'billing_canceled' => $billingCanceled,
            ],
            $orgId,
            $user->id
        ));

        $message = 'Sua conta foi excluída. O acesso foi encerrado.';
        if ($billingCanceled) {
            $message .= ' A assinatura da empresa foi cancelada no gateway de pagamento.';
        }

        return response()->json([
            'data' => [
                'message' => $message,
                'billing_canceled' => $billingCanceled,
            ],
        ]);
    }

    private function countOtherActiveBillingManagers(int $organizationId, int $excludeUserId): int
    {
        return User::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('active', true)
            ->where('id', '!=', $excludeUserId)
            ->get()
            ->filter(fn (User $peer) => $peer->hasPermission(Permission::BILLING_MANAGE))
            ->count();
    }

    private function deleteElectronicSignature(User $user): void
    {
        $path = $user->electronic_signature_path ?? null;
        if ($path === null || $path === '') {
            return;
        }

        try {
            if (Storage::disk('minio_submissions')->exists($path)) {
                Storage::disk('minio_submissions')->delete($path);
            }
        } catch (\Throwable) {
            // disco indisponível — segue fluxo
        }
    }
}
