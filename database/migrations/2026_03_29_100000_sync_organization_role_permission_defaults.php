<?php

use App\Support\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['owner', 'manager', 'staff'] as $slug) {
            $permissions = match ($slug) {
                'owner' => Permission::ownerDefaults(),
                'manager' => Permission::managerDefaults(),
                'staff' => Permission::staffDefaults(),
            };
            DB::table('organization_roles')
                ->where('slug', $slug)
                ->where('is_system', true)
                ->update([
                    'permissions' => json_encode($permissions),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
