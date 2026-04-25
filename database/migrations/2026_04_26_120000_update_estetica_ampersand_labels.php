<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const TEMPLATE_NAME_MAP = [
        'Autorização de Imagem & Proteção de Dados (LGPD)' => 'Autorização de Imagem e Proteção de Dados (LGPD)',
        'Orçamento & Plano de Tratamento' => 'Orçamento e Plano de Tratamento',
        'Anamnese — Laser, Peeling & Procedimentos Lumínicos' => 'Anamnese — Laser, Peeling e Procedimentos Lumínicos',
        'Evolução de Sessão & Acompanhamento' => 'Evolução de Sessão e Acompanhamento',
        'Retorno & Avaliação Pós-Tratamento' => 'Retorno e Avaliação Pós-Tratamento',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::TEMPLATE_NAME_MAP as $from => $to) {
            DB::table('form_templates')->where('name', $from)->update([
                'name' => $to,
                'updated_at' => $now,
            ]);
        }

        DB::table('template_categories')
            ->where('key', 'cadastro_documentacao')
            ->update(['name' => 'Cadastro e Documentação', 'updated_at' => $now]);

        DB::table('template_categories')
            ->where('key', 'acompanhamento_controle')
            ->update(['name' => 'Acompanhamento e Controle', 'updated_at' => $now]);
    }

    public function down(): void
    {
        $now = now();
        foreach (self::TEMPLATE_NAME_MAP as $from => $to) {
            DB::table('form_templates')->where('name', $to)->update([
                'name' => $from,
                'updated_at' => $now,
            ]);
        }

        DB::table('template_categories')
            ->where('key', 'cadastro_documentacao')
            ->update(['name' => 'Cadastro & Documentação', 'updated_at' => $now]);

        DB::table('template_categories')
            ->where('key', 'acompanhamento_controle')
            ->update(['name' => 'Acompanhamento & Controle', 'updated_at' => $now]);
    }
};
