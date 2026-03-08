<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demonstration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('clinic');
            $table->string('email');
            $table->string('phone', 50);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demonstration_requests');
    }
};
