<?php

namespace App\Support;

/**
 * Permissões no contexto de uma organização (empresa). Checadas em Gates e hasPermission no User.
 */
final class Permission
{
    public const DASHBOARD_ACCESS = 'dashboard.access';

    public const NOTIFICATIONS_ACCESS = 'notifications.access';

    public const BILLING_MANAGE = 'billing.manage';

    public const ORGANIZATION_MANAGE = 'organization.manage';

    public const USERS_MANAGE = 'users.manage';

    public const TEMPLATES_MANAGE = 'templates.manage';

    public const SUBMISSIONS_VIEW = 'submissions.view';

    public const SUBMISSIONS_APPROVE = 'submissions.approve';

    public const PEOPLE_DEACTIVATE = 'people.deactivate';

    /** @return list<array{key: string, group: string, group_label: string, label: string, description: string|null}> */
    public static function catalog(): array
    {
        return [
            [
                'key' => self::DASHBOARD_ACCESS,
                'group' => 'geral',
                'group_label' => 'Geral',
                'label' => 'Acessar o painel (dashboard)',
                'description' => 'Ver a página inicial com resumos e indicadores.',
            ],
            [
                'key' => self::NOTIFICATIONS_ACCESS,
                'group' => 'geral',
                'group_label' => 'Geral',
                'label' => 'Acessar notificações',
                'description' => 'Ver e gerenciar notificações do usuário.',
            ],
            [
                'key' => self::BILLING_MANAGE,
                'group' => 'geral',
                'group_label' => 'Geral',
                'label' => 'Assinatura e cobrança',
                'description' => 'Acessar planos, pagamentos e alteração de assinatura.',
            ],
            [
                'key' => self::ORGANIZATION_MANAGE,
                'group' => 'organization',
                'group_label' => 'Organização',
                'label' => 'Configurações e integrações',
                'description' => 'Alterar dados da empresa, link na bio, logs e integrações.',
            ],
            [
                'key' => self::USERS_MANAGE,
                'group' => 'users',
                'group_label' => 'Usuários',
                'label' => 'Gerenciar usuários',
                'description' => 'Convidar, editar perfis e desativar usuários.',
            ],
            [
                'key' => self::TEMPLATES_MANAGE,
                'group' => 'documentos',
                'group_label' => 'Documentos e envios',
                'label' => 'Gerenciar templates e envios',
                'description' => 'Criar/editar templates, campos, links públicos e envios.',
            ],
            [
                'key' => self::SUBMISSIONS_VIEW,
                'group' => 'protocolos',
                'group_label' => 'Protocolos e pessoas',
                'label' => 'Ver protocolos e pessoas',
                'description' => 'Listar e abrir fichas, protocolos e documentos.',
            ],
            [
                'key' => self::SUBMISSIONS_APPROVE,
                'group' => 'protocolos',
                'group_label' => 'Protocolos e pessoas',
                'label' => 'Revisar e aprovar protocolos',
                'description' => 'Aprovar, rejeitar e comentar submissões.',
            ],
            [
                'key' => self::PEOPLE_DEACTIVATE,
                'group' => 'protocolos',
                'group_label' => 'Protocolos e pessoas',
                'label' => 'Inativar pessoas',
                'description' => 'Inativar cadastro de pessoas na organização.',
            ],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::catalog(), 'key');
    }

    /** @return list<string> */
    public static function ownerDefaults(): array
    {
        return self::keys();
    }

    /** @return list<string> */
    public static function managerDefaults(): array
    {
        return [
            self::DASHBOARD_ACCESS,
            self::NOTIFICATIONS_ACCESS,
            self::BILLING_MANAGE,
            self::TEMPLATES_MANAGE,
            self::SUBMISSIONS_VIEW,
            self::SUBMISSIONS_APPROVE,
            self::PEOPLE_DEACTIVATE,
        ];
    }

    /** @return list<string> */
    public static function staffDefaults(): array
    {
        return [
            self::DASHBOARD_ACCESS,
            self::NOTIFICATIONS_ACCESS,
            self::SUBMISSIONS_VIEW,
        ];
    }
}
