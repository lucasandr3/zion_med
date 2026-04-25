<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('template_categories')->delete();
            DB::table('form_templates')->delete();
        });
    }

    public function down(): void
    {
        // Sem rollback automático: esta migração remove os templates anteriores.
    }
};
