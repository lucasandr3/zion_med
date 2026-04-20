<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('key', 'solo')
            ->update([
                'name' => 'Gestgo Profissional',
                'value' => 97.00,
                'description' => 'Plano completo: fichas digitais, consentimentos, assinatura eletrônica, protocolo, PDF, link da bio, templates e usuários ilimitados.',
                'max_users' => null,
                'max_organizations_per_tenant' => 1,
                'is_active' => true,
                'sort_order' => 0,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->where('key', 'executive')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        Plan::clearCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('key', 'solo')
            ->update([
                'name' => 'Gestgo Profissional',
                'value' => 97.00,
                'description' => 'Para autônomos: fichas digitais, consentimentos, assinatura eletrônica, protocolo, PDF, link da bio e templates — até 2 usuários.',
                'max_users' => 2,
                'max_organizations_per_tenant' => 1,
                'is_active' => true,
                'sort_order' => 0,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->where('key', 'executive')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

        Plan::clearCache();
    }
};
