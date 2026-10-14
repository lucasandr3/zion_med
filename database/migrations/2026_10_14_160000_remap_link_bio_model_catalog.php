<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remapeia organizations.link_bio_model para o catálogo Gestgo 2026-10:
 * 1 Solo, 2 Genérico, 3 Estética, 4 Odontologia, 5 Nutrição,
 * 6 Pediatria, 7 Multiprofissional, 8 Fisioterapia dermatofuncional.
 *
 * Catálogo antigo: 1 Genérico, 2 Solo, 3 Estética, 4 Odonto,
 * 5 Multi, 6 Vet, 7 Pediatria, 8 Nutri.
 * Veterinária (6) migra para Genérico (2).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Etapa 1: valores temporários para evitar colisão em swaps (1↔2 etc.).
        DB::statement('UPDATE organizations SET link_bio_model = CASE link_bio_model
            WHEN 1 THEN 102
            WHEN 2 THEN 101
            WHEN 3 THEN 103
            WHEN 4 THEN 104
            WHEN 5 THEN 107
            WHEN 6 THEN 102
            WHEN 7 THEN 106
            WHEN 8 THEN 105
            ELSE 102
        END');

        DB::statement('UPDATE organizations SET link_bio_model = CASE link_bio_model
            WHEN 101 THEN 1
            WHEN 102 THEN 2
            WHEN 103 THEN 3
            WHEN 104 THEN 4
            WHEN 105 THEN 5
            WHEN 106 THEN 6
            WHEN 107 THEN 7
            ELSE 2
        END');
    }

    public function down(): void
    {
        // Reverso aproximado: Genérico (2) não distingue quem era Vet vs Genérico antigo.
        DB::statement('UPDATE organizations SET link_bio_model = CASE link_bio_model
            WHEN 1 THEN 201
            WHEN 2 THEN 202
            WHEN 3 THEN 203
            WHEN 4 THEN 204
            WHEN 5 THEN 208
            WHEN 6 THEN 207
            WHEN 7 THEN 205
            WHEN 8 THEN 202
            ELSE 202
        END');

        DB::statement('UPDATE organizations SET link_bio_model = CASE link_bio_model
            WHEN 201 THEN 2
            WHEN 202 THEN 1
            WHEN 203 THEN 3
            WHEN 204 THEN 4
            WHEN 205 THEN 5
            WHEN 207 THEN 7
            WHEN 208 THEN 8
            ELSE 1
        END');
    }
};
