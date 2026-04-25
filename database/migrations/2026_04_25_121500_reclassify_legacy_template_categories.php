<?php

declare(strict_types=1);

use App\Models\FormTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $templates = DB::table('form_templates')
            ->select('id', 'organization_id', 'name', 'category')
            ->whereNotNull('organization_id')
            ->get();

        foreach ($templates as $t) {
            $nextCategory = $this->resolveCategory((string) $t->name, $t->category ? (string) $t->category : null);
            if ($nextCategory !== ($t->category ? (string) $t->category : null)) {
                DB::table('form_templates')
                    ->where('id', $t->id)
                    ->update([
                        'category' => $nextCategory,
                        'updated_at' => $now,
                    ]);
            }

            if ($nextCategory !== null && $nextCategory !== '') {
                DB::table('template_categories')->updateOrInsert(
                    [
                        'organization_id' => (int) $t->organization_id,
                        'key' => $nextCategory,
                    ],
                    [
                        'name' => FormTemplate::categoryLabels()[$nextCategory] ?? $nextCategory,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Sem rollback automático para evitar perda de classificação já ajustada em produção.
    }

    private function resolveCategory(string $name, ?string $current): ?string
    {
        $n = mb_strtolower(trim($name));

        if ($n === '') {
            return $current;
        }

        if (str_contains($n, 'anamnese') || str_contains($n, 'cadastro do paciente')) {
            return 'anamnese';
        }

        if (str_contains($n, 'acompanhamento') || str_contains($n, 'retorno') || str_contains($n, 'pós-parto') || str_contains($n, 'pos-parto')) {
            return 'acompanhamento';
        }

        if (str_contains($n, 'evolução') || str_contains($n, 'evolucao') || str_contains($n, 'reavaliação') || str_contains($n, 'reavaliacao')) {
            return 'evolucao';
        }

        if (
            str_contains($n, 'consentimento')
            || str_contains($n, 'termo')
            || str_contains($n, 'autorização')
            || str_contains($n, 'autorizacao')
            || str_contains($n, 'declaração')
            || str_contains($n, 'declaracao')
        ) {
            return 'consentimento';
        }

        if (str_contains($n, 'triagem') || str_contains($n, 'checklist')) {
            return 'triagem';
        }

        if (
            str_contains($n, 'procedimento')
            || str_contains($n, 'solicitação')
            || str_contains($n, 'solicitacao')
            || str_contains($n, 'ficha do procedimento')
        ) {
            return 'procedimento';
        }

        if (str_contains($n, 'pesquisa de satisfação') || str_contains($n, 'pesquisa de satisfacao')) {
            return 'acompanhamento';
        }

        return $current;
    }
};
