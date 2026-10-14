<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=dom .. 6=sab
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'weekday', 'is_active']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_service_id')->constrained('appointment_services')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 32)->default('confirmed'); // confirmed|cancelled|completed
            $table->string('patient_name', 160);
            $table->string('patient_email', 191)->nullable();
            $table->string('patient_phone', 40)->nullable();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
            $table->string('public_token', 64)->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'starts_at', 'appointment_service_id'], 'appointments_org_start_service_unique');
            $table->index(['organization_id', 'starts_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('appointment_services');
    }
};
