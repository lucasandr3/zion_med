<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('people', 'cpf_hash')) {
            return;
        }

        $indexNames = collect(Schema::getIndexes('people'))->pluck('name')->all();
        if (in_array('people_cpf_hash_index', $indexNames, true)) {
            return;
        }

        Schema::table('people', function (Blueprint $table) {
            $table->index('cpf_hash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('people', 'cpf_hash')) {
            return;
        }

        $indexNames = collect(Schema::getIndexes('people'))->pluck('name')->all();
        if (! in_array('people_cpf_hash_index', $indexNames, true)) {
            return;
        }

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['cpf_hash']);
        });
    }
};
