<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('form_templates')
            ->where('category', 'Acompanhamento_controle')
            ->update(['category' => 'acompanhamento_controle', 'updated_at' => $now]);

        // Evita duplicar (organization_id, key) ao renomear: remove pasta com chave typo.
        DB::table('template_categories')
            ->where('key', 'Acompanhamento_controle')
            ->delete();

        DB::table('template_categories')
            ->where('key', 'acompanhamento_controle')
            ->update(['name' => 'Acompanhamento e Controle', 'updated_at' => $now]);
    }

    public function down(): void
    {
        $now = now();

        DB::table('form_templates')
            ->where('category', 'acompanhamento_controle')
            ->where('name', 'Evolução de Sessão e Acompanhamento')
            ->update(['category' => 'Acompanhamento_controle', 'updated_at' => $now]);
    }
};
