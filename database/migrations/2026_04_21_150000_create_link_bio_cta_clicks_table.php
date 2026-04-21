<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_bio_cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('channel', 32);
            $table->string('ref', 32)->default('');
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'channel', 'ref', 'date'], 'link_bio_cta_clicks_org_channel_ref_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_bio_cta_clicks');
    }
};
