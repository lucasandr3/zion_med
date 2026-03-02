<?php

use App\Models\Clinic;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $clinics = Clinic::whereNull('tenant_id')->get();

        foreach ($clinics as $clinic) {
            $slug = $this->uniqueTenantSlug(Str::slug($clinic->name));
            $tenant = Tenant::create([
                'name' => $clinic->name,
                'slug' => $slug,
            ]);
            $clinic->update(['tenant_id' => $tenant->id]);
        }
    }

    public function down(): void
    {
        Clinic::whereNotNull('tenant_id')->update(['tenant_id' => null]);
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
