<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all();
        if ($clinics->isEmpty()) {
            return;
        }

        foreach ($clinics as $clinic) {
            $alreadyHasTemplates = FormTemplate::withoutGlobalScopes()
                ->where('organization_id', $clinic->id)
                ->whereNotNull('category')
                ->exists();
            if ($alreadyHasTemplates) {
                continue;
            }

            $owner = User::withoutGlobalScopes()->where('organization_id', $clinic->id)->first();
            self::seedTemplatesForClinic($clinic, $owner);
        }
    }

    /**
     * Cria os templates padrão (FormTemplateDefinitions) para uma clínica.
     * Usado no cadastro de novo tenant e pelo run() do seeder.
     */
    public static function seedTemplatesForClinic(Clinic $clinic, ?User $owner = null): void
    {
        $templates = FormTemplateDefinitions::all();

        foreach ($templates as $t) {
            $fields = $t['fields'];
            $category = $t['category'];
            unset($t['fields'], $t['category']);

            $template = FormTemplate::withoutGlobalScopes()->create([
                'organization_id' => $clinic->id,
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
}
