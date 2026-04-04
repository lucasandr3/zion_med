<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('key', 'executive')
            ->where('name', 'Gestgo Clínica')
            ->update(['name' => 'Gestgo Business', 'updated_at' => now()]);

        Plan::clearCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('key', 'executive')
            ->where('name', 'Gestgo Business')
            ->update(['name' => 'Gestgo Clínica', 'updated_at' => now()]);

        Plan::clearCache();
    }
};
