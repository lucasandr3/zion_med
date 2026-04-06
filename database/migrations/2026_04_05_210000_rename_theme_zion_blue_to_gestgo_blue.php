<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')->where('theme', 'zion-blue')->update(['theme' => 'gestgo-blue']);
        DB::table('organizations')->where('public_theme', 'zion-blue')->update(['public_theme' => 'gestgo-blue']);
    }

    /**
     * Reversão omitida: não é possível distinguir linhas migradas de registros já em gestgo-blue.
     */
    public function down(): void
    {
    }
};
