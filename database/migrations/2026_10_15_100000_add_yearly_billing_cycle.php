<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'value_yearly')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->decimal('value_yearly', 10, 2)->nullable()->after('value');
            });

            // Anual = 10 × mensal (12 meses pelo preço de 10).
            DB::table('plans')
                ->whereNull('value_yearly')
                ->update([
                    'value_yearly' => DB::raw('ROUND(value * 10, 2)'),
                ]);
        }

        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'cycle')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('cycle', 16)->default('MONTHLY')->after('billing_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'cycle')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('cycle');
            });
        }

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'value_yearly')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('value_yearly');
            });
        }
    }
};
