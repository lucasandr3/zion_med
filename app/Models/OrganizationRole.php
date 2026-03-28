<?php

namespace App\Models;

use App\Support\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationRole extends Model
{
    protected $fillable = [
        'organization_id',
        'slug',
        'label',
        'is_system',
        'is_assignable',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_assignable' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public static function seedDefaultsForOrganization(int $organizationId): void
    {
        $now = now();
        $rows = [
            [
                'organization_id' => $organizationId,
                'slug' => 'owner',
                'label' => 'Proprietário',
                'is_system' => true,
                'is_assignable' => true,
                'permissions' => Permission::ownerDefaults(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'organization_id' => $organizationId,
                'slug' => 'manager',
                'label' => 'Gerente',
                'is_system' => true,
                'is_assignable' => true,
                'permissions' => Permission::managerDefaults(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'organization_id' => $organizationId,
                'slug' => 'staff',
                'label' => 'Equipe',
                'is_system' => true,
                'is_assignable' => true,
                'permissions' => Permission::staffDefaults(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        foreach ($rows as $row) {
            static::query()->firstOrCreate(
                [
                    'organization_id' => $row['organization_id'],
                    'slug' => $row['slug'],
                ],
                $row
            );
        }
    }

    public function usersCount(): int
    {
        return User::withoutGlobalScopes()
            ->where('organization_id', $this->organization_id)
            ->where('role', $this->slug)
            ->count();
    }
}
