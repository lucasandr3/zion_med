<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_submissions_are_scoped_by_clinic(): void
    {
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);

        $clinic1 = Clinic::first();
        $user1 = User::withoutGlobalScopes()->where('clinic_id', $clinic1->id)->first();
        $template1 = FormTemplate::withoutGlobalScopes()->where('clinic_id', $clinic1->id)->first();

        $tenant2 = Tenant::create(['name' => 'Outra Clínica', 'slug' => 'outra-clinica']);
        $clinic2 = Clinic::create(['tenant_id' => $tenant2->id, 'name' => 'Outra Clínica', 'slug' => 'outra-clinica', 'notification_email' => null]);
        $user2 = User::withoutGlobalScopes()->create([
            'clinic_id' => $clinic2->id,
            'name' => 'User 2',
            'email' => 'user2@outra.com',
            'password' => bcrypt('senha'),
            'role' => Role::Owner,
            'active' => true,
        ]);
        $template2 = FormTemplate::withoutGlobalScopes()->create([
            'clinic_id' => $clinic2->id,
            'name' => 'Template 2',
            'is_active' => true,
            'public_enabled' => false,
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'clinic_id' => $clinic1->id,
            'template_id' => $template1->id,
            'status' => 'pending',
            'protocol_number' => 'ZM-AAA-001',
        ]);
        FormSubmission::withoutGlobalScopes()->create([
            'clinic_id' => $clinic2->id,
            'template_id' => $template2->id,
            'status' => 'pending',
            'protocol_number' => 'ZM-BBB-002',
        ]);

        $this->actingAs($user1);
        session(['current_clinic_id' => $clinic1->id]);
        $response = $this->get(route('protocolos.index'));
        $response->assertStatus(200);
        $response->assertSee('ZM-AAA-001');
        $response->assertDontSee('ZM-BBB-002');
    }
}
