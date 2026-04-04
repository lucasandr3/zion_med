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

        $renames = [
            'solo' => [
                'ZionMed Profissional' => 'Gestgo Profissional',
            ],
            'executive' => [
                'ZionMed Clínica' => 'Gestgo Business',
            ],
        ];

        foreach ($renames as $key => $map) {
            foreach ($map as $from => $to) {
                DB::table('plans')
                    ->where('key', $key)
                    ->where('name', $from)
                    ->update(['name' => $to, 'updated_at' => now()]);
            }
        }

        Plan::clearCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        $renames = [
            'solo' => [
                'Gestgo Profissional' => 'ZionMed Profissional',
            ],
            'executive' => [
                'Gestgo Business' => 'ZionMed Clínica',
            ],
        ];

        foreach ($renames as $key => $map) {
            foreach ($map as $from => $to) {
                DB::table('plans')
                    ->where('key', $key)
                    ->where('name', $from)
                    ->update(['name' => $to, 'updated_at' => now()]);
            }
        }

        Plan::clearCache();
    }
};
