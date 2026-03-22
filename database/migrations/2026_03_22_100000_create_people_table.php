<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('cpf', 14)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
