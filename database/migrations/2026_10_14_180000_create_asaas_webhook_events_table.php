<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asaas_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 191)->unique();
            $table->string('event_type', 120)->nullable()->index();
            $table->string('asaas_resource_id', 120)->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('status', 32)->default('processed'); // processed|ignored|failed
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asaas_webhook_events');
    }
};
