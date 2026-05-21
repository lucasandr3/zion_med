<?php

declare(strict_types=1);

use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\TemplateCategory;
use App\Models\User;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORY_KEYS = [
        'veterinaria',
        'cadastro_documentacao',
        'consentimento',
        'acompanhamento',
        'acompanhamento_controle',
        'evolucao',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            Organization::query()
                ->where('niche', 'veterinaria')
                ->orderBy('id')
                ->each(function (Organization $organization): void {
                    $hasInternacao = FormTemplate::withoutGlobalScopes()
                        ->where('organization_id', $organization->id)
                        ->where('name', 'Termo de Autorização para Internação e Tratamento Clínico')
                        ->exists();

                    if ($hasInternacao) {
                        return;
                    }

                    FormTemplate::withoutGlobalScopes()
                        ->where('organization_id', $organization->id)
                        ->where(function ($q): void {
                            $q->where('category', 'veterinaria')
                                ->orWhereIn('category', [
                                    'cadastro_documentacao',
                                    'consentimento',
                                    'acompanhamento',
                                    'acompanhamento_controle',
                                    'evolucao',
                                ]);
                        })
                        ->delete();

                    TemplateCategory::query()
                        ->where('organization_id', $organization->id)
                        ->whereIn('key', self::CATEGORY_KEYS)
                        ->delete();

                    $owner = User::withoutGlobalScopes()
                        ->where('organization_id', $organization->id)
                        ->orderBy('id')
                        ->first();

                    FormTemplateSeeder::seedVeterinariaNichePackForOrganization($organization, $owner);
                });
        });
    }

    public function down(): void
    {
        // Sem reversão automática: reexecutar seeders ou restaurar backup.
    }
};
