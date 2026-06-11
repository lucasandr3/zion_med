<?php

namespace App\Support\Connector;

use App\Enums\Role;
use App\Models\DemonstrationRequest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

final class ConnectorMapper
{
    public static function organizationExternalId(int $organizationId): string
    {
        return 'org-'.$organizationId;
    }

    public static function tenantExternalId(int $tenantId): string
    {
        return 'tenant-'.$tenantId;
    }

    public static function userExternalId(int $userId): string
    {
        return 'user-'.$userId;
    }

    public static function subscriptionExternalId(int $subscriptionId): string
    {
        return 'sub-'.$subscriptionId;
    }

    public static function paymentExternalId(int $paymentId): string
    {
        return 'pay-'.$paymentId;
    }

    public static function leadExternalId(int $leadId): string
    {
        return 'lead-'.$leadId;
    }

    /**
     * @return array<string, mixed>
     */
    public static function empresa(Organization $organization): array
    {
        $razaoSocial = trim((string) ($organization->billing_name ?: $organization->name));
        $nomeFantasia = $organization->billing_name ? $organization->name : null;

        return [
            'external_id' => self::organizationExternalId((int) $organization->id),
            'razao_social' => $razaoSocial !== '' ? $razaoSocial : $organization->name,
            'nome_fantasia' => $nomeFantasia,
            'cnpj' => self::formatDocument((string) ($organization->billing_document ?? '')),
            'segmento' => $organization->niche,
            'telefone' => $organization->phone,
            'email' => $organization->contact_email ?: $organization->billing_email,
            'site' => $organization->slug ? rtrim((string) config('app.url'), '/').'/bio/'.$organization->slug : null,
            'status' => self::empresaStatus($organization),
            'data_cadastro' => $organization->created_at?->toDateString(),
            'ultima_interacao' => $organization->updated_at?->toDateString(),
            'plan_key' => $organization->plan_key,
            'subscription_status' => $organization->subscription_status,
            'billing_status' => $organization->billing_status,
            'tenant_id' => $organization->tenant_id,
            'created_at' => $organization->created_at?->toIso8601String(),
            'updated_at' => $organization->updated_at?->toIso8601String(),
            'deleted_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function cliente(Tenant $tenant, ?Organization $primaryOrganization): array
    {
        $documento = self::formatDocument((string) ($primaryOrganization?->billing_document ?? ''));

        return [
            'external_id' => self::tenantExternalId((int) $tenant->id),
            'empresa_external_id' => $primaryOrganization
                ? self::organizationExternalId((int) $primaryOrganization->id)
                : self::tenantExternalId((int) $tenant->id),
            'nome' => $tenant->name,
            'tipo' => 'PJ',
            'documento' => $documento !== '' ? $documento : '00000000000000',
            'telefone' => $primaryOrganization?->phone,
            'email' => $primaryOrganization?->billing_email ?: $primaryOrganization?->contact_email,
            'status' => self::tenantHasActiveOrganization($tenant) ? 'ATIVO' : 'INATIVO',
            'created_at' => $tenant->created_at?->toIso8601String(),
            'updated_at' => $tenant->updated_at?->toIso8601String(),
            'deleted_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function contato(User $user): array
    {
        $role = Role::tryFrom((string) $user->role);

        return [
            'external_id' => self::userExternalId((int) $user->id),
            'empresa_external_id' => self::organizationExternalId((int) $user->organization_id),
            'nome' => $user->name,
            'cargo' => $role?->label(),
            'departamento' => self::departamentoFromRole($role),
            'email' => $user->email,
            'telefone' => null,
            'whatsapp' => null,
            'linkedin' => null,
            'decisor' => $role === Role::Owner || $role === Role::SuperAdmin,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'deleted_at' => $user->active ? null : $user->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function lead(DemonstrationRequest $lead): array
    {
        $clinic = trim((string) ($lead->clinic ?? ''));
        $name = trim((string) ($lead->name ?? ''));

        return [
            'external_id' => self::leadExternalId((int) $lead->id),
            'empresa_external_id' => null,
            'nome' => $name !== '' ? $name : null,
            'empresa_nome' => $clinic !== '' ? $clinic : ($name !== '' ? $name : 'Sem clínica'),
            'email' => $lead->email,
            'telefone' => $lead->phone,
            'mensagem' => $lead->message,
            'origem' => 'Site',
            'responsavel' => '',
            'status' => 'NOVO',
            'valor_estimado' => 0,
            'previsao_fechamento' => null,
            'created_at' => $lead->created_at?->toIso8601String(),
            'updated_at' => ($lead->updated_at ?? $lead->created_at)?->toIso8601String(),
            'deleted_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function assinatura(Subscription $subscription): array
    {
        $organization = $subscription->organization;
        $plan = $organization?->planDefinition() ?? config('asaas.plans.'.$subscription->plan_key, []);
        $monthlyValue = isset($plan['value']) ? (float) $plan['value'] : 0.0;

        return [
            'external_id' => self::subscriptionExternalId((int) $subscription->id),
            'empresa_external_id' => self::organizationExternalId((int) $subscription->organization_id),
            'nome_plano' => (string) ($plan['name'] ?? $subscription->plan_key ?? 'Plano'),
            'descricao' => isset($plan['description']) ? (string) $plan['description'] : null,
            'valor_mensal' => $monthlyValue,
            'valor_anual' => round($monthlyValue * 12, 2),
            'status' => self::assinaturaStatus($subscription),
            'data_inicio' => $subscription->created_at?->toDateString(),
            'data_renovacao' => $subscription->next_due_date?->toDateString()
                ?? $subscription->current_period_end?->toDateString(),
            'data_cancelamento' => self::assinaturaCancelDate($subscription),
            'created_at' => $subscription->created_at?->toIso8601String(),
            'updated_at' => $subscription->updated_at?->toIso8601String(),
            'deleted_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fatura(Payment $payment): array
    {
        $organizationName = $payment->organization?->name ?? 'Empresa';

        return [
            'external_id' => self::paymentExternalId((int) $payment->id),
            'assinatura_external_id' => $payment->subscription_id
                ? self::subscriptionExternalId((int) $payment->subscription_id)
                : self::paymentExternalId((int) $payment->id),
            'empresa_nome' => $organizationName,
            'numero' => $payment->asaas_payment_id ?: ('PAY-'.$payment->id),
            'valor' => (float) $payment->value,
            'vencimento' => $payment->due_date?->toDateString(),
            'pagamento' => $payment->paid_at?->toDateString(),
            'status' => self::faturaStatus($payment),
            'asaas_payment_id' => $payment->asaas_payment_id,
            'bank_slip_url' => $payment->bank_slip_url,
            'organization_external_id' => $payment->organization_id
                ? self::organizationExternalId((int) $payment->organization_id)
                : null,
            'created_at' => $payment->created_at?->toIso8601String(),
            'updated_at' => $payment->updated_at?->toIso8601String(),
            'deleted_at' => null,
        ];
    }

    private static function empresaStatus(Organization $organization): string
    {
        if ($organization->isOnTrial()) {
            return 'PROSPECT';
        }

        if ($organization->subscription_status === 'active' || $organization->hasConfirmedBillingPayment()) {
            return 'ATIVA';
        }

        return 'INATIVA';
    }

    private static function tenantHasActiveOrganization(Tenant $tenant): bool
    {
        return Organization::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('subscription_status', ['active', 'trial', 'past_due'])
            ->exists();
    }

    private static function departamentoFromRole(?Role $role): ?string
    {
        return match ($role) {
            Role::Owner, Role::SuperAdmin => 'COMERCIAL',
            Role::Manager => 'ADMINISTRATIVO',
            Role::Staff => 'OPERACIONAL',
            default => null,
        };
    }

    private static function assinaturaStatus(Subscription $subscription): string
    {
        $status = strtoupper((string) $subscription->status);

        return match (true) {
            in_array($status, ['CANCELED', 'CANCELLED', 'DELETED', 'INACTIVE'], true) => 'CANCELADA',
            in_array($status, ['PAST_DUE', 'OVERDUE'], true) => 'A_VENCER',
            in_array($status, ['SUSPENDED', 'SUSPENSA'], true) => 'SUSPENSA',
            default => 'ATIVA',
        };
    }

    private static function assinaturaCancelDate(Subscription $subscription): ?string
    {
        $status = strtoupper((string) $subscription->status);
        if (! in_array($status, ['CANCELED', 'CANCELLED', 'DELETED', 'INACTIVE'], true)) {
            return null;
        }

        return $subscription->updated_at?->toDateString();
    }

    private static function faturaStatus(Payment $payment): string
    {
        $status = strtoupper((string) $payment->status);

        return match (true) {
            in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true) => 'PAGA',
            $status === 'OVERDUE' => 'ATRASADA',
            in_array($status, ['CANCELED', 'CANCELLED', 'REFUNDED'], true) => 'CANCELADA',
            default => 'PENDENTE',
        };
    }

    private static function formatDocument(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document) ?? '';

        if (strlen($digits) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits) ?? $digits;
        }

        if (strlen($digits) === 11) {
            return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits) ?? $digits;
        }

        return $document;
    }
}
