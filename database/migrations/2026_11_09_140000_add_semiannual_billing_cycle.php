<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'value_semiannual')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->decimal('value_semiannual', 10, 2)->nullable()->after('value_yearly');
            });

            // Semestral = 5 × mensal (6 meses pelo preço de 5).
            DB::table('plans')
                ->whereNull('value_semiannual')
                ->update([
                    'value_semiannual' => DB::raw('ROUND(value * 5, 2)'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'value_semiannual')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('value_semiannual');
            });
        }
    }
};
