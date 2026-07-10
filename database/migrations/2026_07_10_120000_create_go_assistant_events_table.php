<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('go_assistant_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 40);
            $table->string('screen_id', 120)->nullable();
            $table->string('intent_id', 120)->nullable();
            $table->string('route', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('query', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind', 'created_at']);
            $table->index(['user_id', 'screen_id', 'created_at']);
            $table->index(['user_id', 'intent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('go_assistant_events');
    }
};
