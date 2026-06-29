<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        if (! Schema::hasColumn('plans', 'max_users')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_users')->nullable()->after('description');
            });
        }

        if (! Schema::hasColumn('plans', 'max_organizations_per_tenant')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_organizations_per_tenant')->nullable()->after('max_users');
            });
        }

        $now = now();

        DB::table('plans')
            ->whereNull('max_organizations_per_tenant')
            ->update([
                'max_organizations_per_tenant' => 1,
                'updated_at' => $now,
            ]);

        if (! DB::table('plans')->where('key', 'solo')->exists()) {
            DB::table('plans')->insert([
                'key' => 'solo',
                'name' => 'Gestgo Profissional',
                'value' => 97.00,
                'description' => 'Plano completo: fichas digitais, consentimentos, assinatura eletrônica, protocolo, PDF, link da bio, templates e usuários ilimitados.',
                'sort_order' => 0,
                'is_active' => true,
                'max_users' => null,
                'max_organizations_per_tenant' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('plans')->where('key', 'executive')->exists()) {
            DB::table('plans')->insert([
                'key' => 'executive',
                'name' => 'Gestgo Business',
                'value' => 247.00,
                'description' => 'Para clínicas e equipes: tudo do plano Profissional, com limites ampliados de usuários.',
                'sort_order' => 10,
                'is_active' => false,
                'max_users' => null,
                'max_organizations_per_tenant' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Plan::clearCache();
    }

    public function down(): void
    {
        // Correção idempotente — não reverte dados de produção.
    }
};
