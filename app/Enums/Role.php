<?php

namespace App\Enums;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case SuperAdmin = 'super_admin';
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Admin da plataforma',
            self::SuperAdmin => 'Super administrador',
            self::Owner => 'Proprietário',
            self::Manager => 'Gerente',
            self::Staff => 'Equipe',
        };
    }

    /** Pode acessar e trocar entre empresas dentro do mesmo tenant. */
    public function canSwitchClinic(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner], true);
    }

    public function canManageClinic(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner], true);
    }

    public function canManageTemplates(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Manager], true);
    }

    public function canApproveSubmissions(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Manager], true);
    }

    public function canViewSubmissions(): bool
    {
        return true;
    }
}
