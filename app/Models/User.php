<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'password',
        'role',
        'active',
        'can_switch_clinic',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'active' => 'boolean',
            'can_switch_clinic' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
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

    public function isPlatformAdmin(): bool
    {
        return $this->role === Role::PlatformAdmin;
    }

    public function isTenantUser(): bool
    {
        return in_array($this->role, [
            Role::SuperAdmin,
            Role::Owner,
            Role::Manager,
            Role::Staff,
        ], true);
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    /** Pode acessar e trocar entre todas as clínicas (por cargo SuperAdmin ou permissão delegada). */
    public function canSwitchClinic(): bool
    {
        return $this->role->canSwitchClinic() || ($this->can_switch_clinic ?? false);
    }
}
