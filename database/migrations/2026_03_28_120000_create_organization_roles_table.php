<?php

use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('label');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_assignable')->default(true);
            $table->json('permissions');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        foreach (Organization::query()->orderBy('id')->cursor() as $organization) {
            OrganizationRole::seedDefaultsForOrganization((int) $organization->id);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_roles');
    }
};
