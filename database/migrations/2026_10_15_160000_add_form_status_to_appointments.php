<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('form_status', 32)->default('not_applicable')->after('form_template_id');
            $table->foreignId('form_submission_id')
                ->nullable()
                ->after('form_status')
                ->constrained('form_submissions')
                ->nullOnDelete();
        });

        // Reservas já existentes com ficha vinculada ficam pendentes.
        DB::table('appointments')
            ->whereNotNull('form_template_id')
            ->update(['form_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_submission_id');
            $table->dropColumn('form_status');
        });
    }
};
