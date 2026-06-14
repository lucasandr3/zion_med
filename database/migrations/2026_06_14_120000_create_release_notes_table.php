<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_notes', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('items');
            $table->date('released_at');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_notes');
    }
};
