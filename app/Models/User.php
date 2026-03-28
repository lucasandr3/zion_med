<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'role',
        'active',
        'can_switch_clinic',
        'ui_theme',
        'ui_dark_mode',
    ];

    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['organization_id'] ?? null;
    }

    public function setClinicIdAttribute($value): void
    {
        $this->attributes['organization_id'] = $value;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'can_switch_clinic' => 'boolean',
            'ui_dark_mode' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @deprecated Use organization(). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function createdFormTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class, 'created_by');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'submitted_by_user_id');
    }

    public function approvedSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'approved_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function roleEnum(): ?Role
    {
        return Role::tryFrom((string) ($this->attributes['role'] ?? ''));
    }

    /** Contexto da organização atual (sessão API) ou organização do usuário. */
    public function currentOrganizationId(): ?int
    {
        $sid = session('current_clinic_id');

        return $sid !== null && $sid !== '' ? (int) $sid : ($this->organization_id !== null ? (int) $this->organization_id : null);
    }

    /**
     * Permissões efetivas no contexto atual (header/sessão de organização).
     *
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        $enum = $this->roleEnum();
        if ($enum === Role::PlatformAdmin) {
            return Permission::keys();
        }
        if ($enum === Role::SuperAdmin) {
            return Permission::keys();
        }

        $orgId = $this->currentOrganizationId();
        if (! $orgId) {
            return $this->permissionsFallbackFromEnum();
        }

        $row = OrganizationRole::query()
            ->where('organization_id', $orgId)
            ->where('slug', (string) ($this->attributes['role'] ?? ''))
            ->first();

        if ($row && is_array($row->permissions)) {
            return array_values(array_unique(array_intersect($row->permissions, Permission::keys())));
        }

        return $this->permissionsFallbackFromEnum();
    }

    /**
     * @return list<string>
     */
    private function permissionsFallbackFromEnum(): array
    {
        $enum = $this->roleEnum();
        if (! $enum) {
            return [];
        }

        return match ($enum) {
            Role::Owner => Permission::ownerDefaults(),
            Role::Manager => Permission::managerDefaults(),
            Role::Staff => Permission::staffDefaults(),
            default => [],
        };
    }

    public function hasPermission(string $permission): bool
    {
        if (! in_array($permission, Permission::keys(), true)) {
            return false;
        }

        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function resolveRoleLabel(): string
    {
        $slug = (string) ($this->attributes['role'] ?? '');
        $orgId = $this->currentOrganizationId();
        if ($orgId) {
            $label = OrganizationRole::query()
                ->where('organization_id', $orgId)
                ->where('slug', $slug)
                ->value('label');
            if ($label) {
                return $label;
            }
        }
        $enum = Role::tryFrom($slug);

        return $enum ? $enum->label() : $slug;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->roleEnum() === Role::PlatformAdmin;
    }

    public function isTenantUser(): bool
    {
        if ($this->isPlatformAdmin()) {
            return false;
        }

        return $this->roleEnum() !== null || $this->organization_id !== null;
    }

    public function isOwner(): bool
    {
        return (string) ($this->attributes['role'] ?? '') === Role::Owner->value;
    }

    public function isManager(): bool
    {
        return (string) ($this->attributes['role'] ?? '') === Role::Manager->value;
    }

    public function isStaff(): bool
    {
        return (string) ($this->attributes['role'] ?? '') === Role::Staff->value;
    }

    /** Pode acessar e trocar entre todas as empresas (SuperAdmin/Owner ou permissão delegada). */
    public function canSwitchClinic(): bool
    {
        $enum = $this->roleEnum();

        return ($enum !== null && $enum->canSwitchClinic()) || (bool) ($this->can_switch_clinic ?? false);
    }
}
