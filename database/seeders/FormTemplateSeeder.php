<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();
        if ($organizations->isEmpty()) {
            return;
        }

        foreach ($organizations as $organization) {
            $alreadyHasTemplates = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->whereNotNull('category')
                ->exists();
            if ($alreadyHasTemplates) {
                continue;
            }

            $owner = User::withoutGlobalScopes()->where('organization_id', $organization->id)->first();
            self::seedTemplatesForOrganization($organization, $owner);
        }
    }

    /**
     * @deprecated Use seedTemplatesForOrganization().
     */
    public static function seedTemplatesForClinic(Clinic $clinic, ?User $owner = null): void
    {
        self::seedTemplatesForOrganization($clinic, $owner);
    }

    /**
     * Cria os templates padrão (FormTemplateDefinitions) para uma organização.
     */
    public static function seedTemplatesForOrganization(Organization $organization, ?User $owner = null): void
    {
        $templates = FormTemplateDefinitions::all();

        foreach ($templates as $t) {
            $fields = $t['fields'];
            $category = $t['category'];
            unset($t['fields'], $t['category']);

            $template = FormTemplate::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'name' => $t['name'],
                'description' => $t['description'],
                'category' => $category,
                'is_active' => true,
                'public_enabled' => false,
                'created_by' => $owner?->id,
            ]);

            foreach ($fields as $f) {
                $opts = $f['options'] ?? null;
                unset($f['options']);
                $f['template_id'] = $template->id;
                $f['options_json'] = $opts ? ['options' => $opts] : null;
                FormField::create($f);
            }
        }
    }

    /**
     * Garante os templates de compliance (telemedicina, LGPD, checklist OMS) para uma organização.
     * Não duplica se já existir template com o mesmo nome na organização.
     */
    public static function ensureComplianceExtrasForOrganization(Organization $organization, ?User $owner = null): int
    {
        $created = 0;

        foreach (FormTemplateDefinitions::complianceExtras() as $t) {
            $exists = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('name', $t['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            $fields = $t['fields'];
            $category = $t['category'];

            $template = FormTemplate::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'name' => $t['name'],
                'description' => $t['description'],
                'category' => $category,
                'is_active' => true,
                'public_enabled' => false,
                'created_by' => $owner?->id,
            ]);

            foreach ($fields as $f) {
                $opts = $f['options'] ?? null;
                unset($f['options']);
                $f['template_id'] = $template->id;
                $f['options_json'] = $opts ? ['options' => $opts] : null;
                FormField::create($f);
            }

            $created++;
        }

        return $created;
    }
}
