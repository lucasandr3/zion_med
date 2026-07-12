<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use App\Models\User;
use App\Services\TemplateLibraryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    public function test_biblioteca_returns_curated_catalog_with_review_metadata(): void
    {
        $user = $this->actingOwner();

        $response = $this->getJson('/api/v1/templates/biblioteca');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'meta' => ['total', 'niche', 'approved_count', 'content_version'],
                    'specialties' => [
                        ['key', 'label', 'templates'],
                    ],
                ],
            ])
            ->assertJsonPath('data.meta.total', fn ($v) => $v > 20);

        $templates = collect($response->json('data.specialties'))->flatMap(fn ($s) => $s['templates']);
        $this->assertTrue($templates->contains(fn ($t) => ($t['legal_review_status'] ?? '') === 'approved'));
        $this->assertTrue($templates->every(fn ($t) => ! empty($t['library_key'])));
    }

    public function test_install_from_library_creates_template_with_lineage(): void
    {
        $user = $this->actingOwner();
        $catalog = app(TemplateLibraryCatalog::class);
        $item = collect($catalog->catalog('estetica')['specialties'])
            ->flatMap(fn ($s) => $s['templates'])
            ->first(fn ($t) => ($t['legal_review_status'] ?? '') === 'approved');
        $this->assertNotNull($item);
        $libraryKey = $item['library_key'];

        $response = $this->postJson("/api/v1/templates/biblioteca/{$libraryKey}/instalar");

        $response->assertCreated()
            ->assertJsonPath('data.library_key', $libraryKey)
            ->assertJsonPath('data.category', $item['category']);

        $template = FormTemplate::withoutGlobalScopes()->find($response->json('data.id'));
        $this->assertNotNull($template);
        $this->assertSame($libraryKey, $template->library_key);
        $this->assertGreaterThan(0, $template->fields()->count());

        $this->postJson("/api/v1/templates/biblioteca/{$libraryKey}/instalar")
            ->assertCreated()
            ->assertJsonPath('data.id', $template->id);
    }

    public function test_biblioteca_show_includes_fields_for_preview(): void
    {
        $user = $this->actingOwner();
        $catalog = app(TemplateLibraryCatalog::class);
        $item = collect($catalog->catalog('estetica')['specialties'])
            ->flatMap(fn ($s) => $s['templates'])
            ->first();
        $this->assertNotNull($item);

        $this->getJson('/api/v1/templates/biblioteca/'.$item['library_key'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['library_key', 'name', 'fields']])
            ->assertJsonPath('data.field_count', fn ($c) => $c > 0);
    }
}
