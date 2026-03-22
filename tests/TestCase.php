<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Dono da clínica de QA (o seeder não cria usuário de tenant; os testes usam este usuário fixo).
     */
    protected function qaClinicOwnerUser(): User
    {
        $clinic = Clinic::query()->firstOrFail();

        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'qa-owner@zionmed.test'],
            [
                'organization_id' => $clinic->id,
                'name' => 'QA Clínica',
                'password' => 'senha123',
                'role' => Role::Owner,
                'active' => true,
            ]
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user->fresh();
    }
}
