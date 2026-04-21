<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feegow_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->unsignedBigInteger('feegow_appointment_id');
            $table->string('status', 30)->default('created');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'person_id']);
            $table->unique(['organization_id', 'feegow_appointment_id']);
            $table->index(['organization_id', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feegow_appointments');
    }
};
