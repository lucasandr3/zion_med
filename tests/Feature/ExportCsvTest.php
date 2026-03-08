<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_csv_requires_authentication(): void
    {
        $response = $this->get(route('protocolos.exportar'));
        $response->assertRedirect(route('login'));
    }

    public function test_export_csv_returns_csv_for_authenticated_user(): void
    {
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $user = User::withoutGlobalScopes()->where('email', 'admin@demo.zionmed.com')->first();
        $this->actingAs($user);
        session(['current_clinic_id' => $user->clinic_id]);

        $response = $this->get(route('protocolos.exportar'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
