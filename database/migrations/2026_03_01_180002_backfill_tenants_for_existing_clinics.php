<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Usa diretamente a tabela física "clinics" para evitar depender do modelo Clinic,
        // que hoje é um alias para Organization (tabela "organizations").
        $clinics = DB::table('clinics')
            ->whereNull('tenant_id')
            ->get();

        foreach ($clinics as $clinic) {
            $slug = $this->uniqueTenantSlug(Str::slug($clinic->name));

            $tenant = Tenant::create([
                'name' => $clinic->name,
                'slug' => $slug,
            ]);

            DB::table('clinics')
                ->where('id', $clinic->id)
                ->update(['tenant_id' => $tenant->id]);
        }
    }

    public function down(): void
    {
        DB::table('clinics')
            ->whereNotNull('tenant_id')
            ->update(['tenant_id' => null]);
    }

    private function uniqueTenantSlug(string $base): string
    {
        $slug = $base;
        $n = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }
};
