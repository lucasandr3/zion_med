<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32); // suggestion | bug
            $table->string('title', 160);
            $table->text('message');
            $table->string('source', 40)->nullable(); // account_menu | go_assistant
            $table->string('page_url', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_feedback');
    }
};
